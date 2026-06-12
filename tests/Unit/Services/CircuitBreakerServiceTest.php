<?php

namespace Tests\Unit\Services;

use App\Services\Resilience\CircuitBreakerService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CircuitBreakerServiceTest extends TestCase
{
    private CircuitBreakerService $breaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->breaker = new CircuitBreakerService(
            failureThreshold: 3,
            successThreshold: 2,
            timeoutSeconds: 60
        );
        // Clear all breaker state
        Cache::flush();
    }

    public function test_closed_circuit_passes_calls_through(): void
    {
        $result = $this->breaker->call('test_service', fn () => 'ok');

        $this->assertSame('ok', $result);
    }

    public function test_circuit_opens_after_failure_threshold(): void
    {
        $failingCallable = fn () => throw new \RuntimeException('API down');

        for ($i = 0; $i < 3; $i++) {
            try {
                $this->breaker->call('test_service', $failingCallable);
            } catch (\RuntimeException) {
                // expected
            }
        }

        $status = $this->breaker->status('test_service');
        $this->assertSame('open', $status['state']);
    }

    public function test_open_circuit_throws_when_no_fallback(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/circuit open/');

        // Open the circuit manually by forcing failures
        for ($i = 0; $i < 3; $i++) {
            try {
                $this->breaker->call('test_service', fn () => throw new \RuntimeException('down'));
            } catch (\RuntimeException) {
            }
        }

        // Now a fresh call should fail-fast without executing the callable
        $this->breaker->call('test_service', fn () => 'should not run');
    }

    public function test_open_circuit_returns_fallback(): void
    {
        // Open the circuit
        for ($i = 0; $i < 3; $i++) {
            try {
                $this->breaker->call('test_service', fn () => throw new \RuntimeException('down'));
            } catch (\RuntimeException) {
            }
        }

        $result = $this->breaker->call(
            'test_service',
            fn () => 'should not run',
            fn () => 'fallback_value'
        );

        $this->assertSame('fallback_value', $result);
    }

    public function test_circuit_reset_restores_closed_state(): void
    {
        // Open the circuit
        for ($i = 0; $i < 3; $i++) {
            try {
                $this->breaker->call('test_service', fn () => throw new \RuntimeException('down'));
            } catch (\RuntimeException) {
            }
        }

        $this->assertSame('open', $this->breaker->status('test_service')['state']);

        $this->breaker->reset('test_service');

        $this->assertSame('closed', $this->breaker->status('test_service')['state']);
    }

    public function test_status_returns_expected_structure(): void
    {
        $status = $this->breaker->status('test_service');

        $this->assertArrayHasKey('service', $status);
        $this->assertArrayHasKey('state', $status);
        $this->assertArrayHasKey('failures', $status);
        $this->assertArrayHasKey('successes', $status);
        $this->assertArrayHasKey('threshold', $status);
        $this->assertSame('closed', $status['state']);
        $this->assertSame(0, $status['failures']);
    }

    public function test_different_services_have_independent_circuits(): void
    {
        // Open circuit for service_a
        for ($i = 0; $i < 3; $i++) {
            try {
                $this->breaker->call('service_a', fn () => throw new \RuntimeException('down'));
            } catch (\RuntimeException) {
            }
        }

        // service_b should still be closed
        $result = $this->breaker->call('service_b', fn () => 'b_ok');
        $this->assertSame('b_ok', $result);
        $this->assertSame('closed', $this->breaker->status('service_b')['state']);
        $this->assertSame('open', $this->breaker->status('service_a')['state']);
    }

    // ── HALF-OPEN state transitions ───────────────────────────────────────────

    public function test_half_open_probe_success_closes_circuit_after_threshold(): void
    {
        $service = 'half-open-recover';

        // Force the circuit into HALF-OPEN state by seeding the cache key directly
        Cache::put("circuit_breaker_{$service}_state", 'half_open', 300);

        // successThreshold = 2 — two successful probes should close the circuit
        $this->breaker->call($service, fn () => 'probe-1');
        $this->breaker->call($service, fn () => 'probe-2');

        $this->assertSame('closed', $this->breaker->status($service)['state'],
            'Circuit should be CLOSED after successThreshold consecutive probe successes.'
        );
    }

    public function test_half_open_probe_failure_reopens_circuit(): void
    {
        $service = 'half-open-fail';

        // Force HALF-OPEN
        Cache::put("circuit_breaker_{$service}_state", 'half_open', 300);

        try {
            $this->breaker->call($service, fn () => throw new \RuntimeException('probe failed'));
        } catch (\RuntimeException) {
            // expected — probe throws
        }

        $this->assertSame('open', $this->breaker->status($service)['state'],
            'Circuit should return to OPEN when a HALF-OPEN probe fails.'
        );
    }

    public function test_circuit_transitions_to_half_open_after_timeout_elapses(): void
    {
        $service = 'timeout-elapsed';

        // Seed an "open" circuit whose half-open window has already passed.
        // When a HALF-OPEN probe fails, recordFailure() sets state back to OPEN and
        // then calls maybeMoveToHalfOpen(). Since _half_open_at is in the past it
        // immediately re-transitions to HALF_OPEN — confirming the elapsed-timeout logic.
        Cache::put("circuit_breaker_{$service}_state", 'half_open', 300);
        Cache::put(
            "circuit_breaker_{$service}_state_half_open_at",
            now()->subSeconds(10)->timestamp,  // already elapsed
            300
        );

        try {
            $this->breaker->call($service, fn () => throw new \RuntimeException('timed-out probe'));
        } catch (\RuntimeException) {
            // expected
        }

        // recordFailure(HALF_OPEN) → sets OPEN → maybeMoveToHalfOpen finds elapsed _half_open_at → HALF_OPEN
        $this->assertSame('half_open', $this->breaker->status($service)['state'],
            'Circuit should re-enter HALF-OPEN immediately when _half_open_at has already elapsed.'
        );
    }
}
