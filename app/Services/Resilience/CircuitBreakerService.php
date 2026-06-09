<?php

namespace App\Services\Resilience;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Circuit Breaker Service
 *
 * Prevents cascading failures when external APIs (OpenAI, Stripe, etc.) are
 * unavailable. Uses Cache as a distributed state store so the breaker is
 * shared across all queue workers and web processes.
 *
 * States:
 *   CLOSED   — normal operation; requests are allowed through
 *   OPEN     — failure threshold exceeded; requests fail-fast with fallback
 *   HALF-OPEN — after cooldown period; one probe request is allowed
 */
class CircuitBreakerService
{
    private const STATE_CLOSED = 'closed';

    private const STATE_OPEN = 'open';

    private const STATE_HALF_OPEN = 'half_open';

    private const DEFAULT_FAILURE_THRESHOLD = 5;

    private const DEFAULT_SUCCESS_THRESHOLD = 2;

    private const DEFAULT_TIMEOUT_SECONDS = 60;

    public function __construct(
        private readonly int $failureThreshold = self::DEFAULT_FAILURE_THRESHOLD,
        private readonly int $successThreshold = self::DEFAULT_SUCCESS_THRESHOLD,
        private readonly int $timeoutSeconds = self::DEFAULT_TIMEOUT_SECONDS,
    ) {}

    /**
     * Execute a callable wrapped with circuit breaker protection.
     *
     * @template T
     *
     * @param  string  $service  Unique circuit identifier (e.g. 'openai', 'stripe')
     * @param  callable(): T  $callable  The operation to execute
     * @param  callable(): T|null  $fallback  Optional fallback when circuit is OPEN
     * @return T
     *
     * @throws \RuntimeException when circuit is open and no fallback is provided
     */
    public function call(string $service, callable $callable, ?callable $fallback = null): mixed
    {
        $state = $this->getState($service);

        if ($state === self::STATE_OPEN) {
            Log::warning('[CircuitBreaker] Circuit OPEN — rejecting request', [
                'service' => $service,
                'failures' => $this->getFailureCount($service),
            ]);

            if ($fallback !== null) {
                return $fallback();
            }

            throw new \RuntimeException(
                "Service '{$service}' is currently unavailable (circuit open). Please retry later."
            );
        }

        if ($state === self::STATE_HALF_OPEN) {
            Log::info('[CircuitBreaker] Circuit HALF-OPEN — sending probe request', [
                'service' => $service,
            ]);
        }

        try {
            $result = $callable();
            $this->recordSuccess($service, $state);

            return $result;
        } catch (\Throwable $e) {
            $this->recordFailure($service, $state);

            Log::error('[CircuitBreaker] Service call failed', [
                'service' => $service,
                'error' => $e->getMessage(),
                'failures' => $this->getFailureCount($service),
                'state' => $this->getState($service),
            ]);

            throw $e;
        }
    }

    /**
     * Manually reset a circuit (e.g. after a deployment or manual remediation).
     */
    public function reset(string $service): void
    {
        Cache::forget($this->stateKey($service));
        Cache::forget($this->failureKey($service));
        Cache::forget($this->successKey($service));

        Log::info('[CircuitBreaker] Circuit manually reset', ['service' => $service]);
    }

    /**
     * Get diagnostic info for all known circuit breakers.
     */
    public function status(string $service): array
    {
        return [
            'service' => $service,
            'state' => $this->getState($service),
            'failures' => $this->getFailureCount($service),
            'successes' => $this->getSuccessCount($service),
            'threshold' => $this->failureThreshold,
        ];
    }

    // ─── Private helpers ──────────────────────────────────────────────────────

    private function getState(string $service): string
    {
        return Cache::get($this->stateKey($service), self::STATE_CLOSED);
    }

    private function getFailureCount(string $service): int
    {
        return (int) Cache::get($this->failureKey($service), 0);
    }

    private function getSuccessCount(string $service): int
    {
        return (int) Cache::get($this->successKey($service), 0);
    }

    private function recordSuccess(string $service, string $priorState): void
    {
        if ($priorState === self::STATE_HALF_OPEN) {
            $successes = $this->getSuccessCount($service) + 1;
            Cache::put($this->successKey($service), $successes, $this->timeoutSeconds * 2);

            if ($successes >= $this->successThreshold) {
                Cache::forget($this->stateKey($service));
                Cache::forget($this->failureKey($service));
                Cache::forget($this->successKey($service));

                Log::info('[CircuitBreaker] Circuit CLOSED — service recovered', [
                    'service' => $service,
                ]);
            }
        }
        // In CLOSED state, successes don't need tracking
    }

    private function recordFailure(string $service, string $priorState): void
    {
        $failures = $this->getFailureCount($service) + 1;
        Cache::put($this->failureKey($service), $failures, $this->timeoutSeconds * 10);

        if ($priorState === self::STATE_HALF_OPEN) {
            // Probe failed — go back to OPEN
            Cache::put($this->stateKey($service), self::STATE_OPEN, $this->timeoutSeconds);
            Cache::forget($this->successKey($service));
        } elseif ($failures >= $this->failureThreshold) {
            // Threshold exceeded — open the circuit
            Cache::put($this->stateKey($service), self::STATE_OPEN, $this->timeoutSeconds);

            Log::critical('[CircuitBreaker] Circuit OPENED — failure threshold exceeded', [
                'service' => $service,
                'failures' => $failures,
                'timeout_seconds' => $this->timeoutSeconds,
            ]);

            // Schedule transition to HALF-OPEN after timeout
            Cache::put(
                $this->stateKey($service).'_half_open_at',
                now()->addSeconds($this->timeoutSeconds)->timestamp,
                $this->timeoutSeconds * 2
            );
        }

        // If we're in OPEN state, check if we should transition to HALF-OPEN
        $this->maybeMoveToHalfOpen($service);
    }

    private function maybeMoveToHalfOpen(string $service): void
    {
        if ($this->getState($service) !== self::STATE_OPEN) {
            return;
        }

        $halfOpenAt = Cache::get($this->stateKey($service).'_half_open_at');
        if ($halfOpenAt && now()->timestamp >= $halfOpenAt) {
            Cache::put($this->stateKey($service), self::STATE_HALF_OPEN, $this->timeoutSeconds);
            Log::info('[CircuitBreaker] Circuit HALF-OPEN — probing service', ['service' => $service]);
        }
    }

    private function stateKey(string $service): string
    {
        return "circuit_breaker_{$service}_state";
    }

    private function failureKey(string $service): string
    {
        return "circuit_breaker_{$service}_failures";
    }

    private function successKey(string $service): string
    {
        return "circuit_breaker_{$service}_successes";
    }
}
