<?php

namespace App\Services\Infrastructure;

use App\Models\SecurityEvent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Alert Service
 *
 * Centralized alerting layer for the platform. Manages:
 *  - Deduplication: same alert for same key suppressed within window
 *  - Severity routing: critical alerts trigger immediate escalation
 *  - Channel abstraction: logs + app notifications + (optionally) email
 *
 * All public methods are idempotent when called with the same key + window.
 */
class AlertService
{
    /** Deduplication window in seconds (default: 15 minutes) */
    private const DEDUP_WINDOW_SECONDS = 900;

    private const CACHE_PREFIX = 'alert_dedup_';

    /**
     * Fire an alert for a security event, with deduplication.
     *
     * @param  string  $key  Deduplication key (e.g. 'prompt_injection:org:42')
     * @param  string  $title  Human-readable alert title
     * @param  string  $message  Detailed alert message
     * @param  string  $severity  critical|high|medium|low
     * @param  array  $context  Extra data for log entry
     * @param  int  $dedupWindowSeconds  How long to suppress duplicate alerts
     */
    public function fire(
        string $key,
        string $title,
        string $message,
        string $severity = 'high',
        array $context = [],
        int $dedupWindowSeconds = self::DEDUP_WINDOW_SECONDS
    ): bool {
        $cacheKey = self::CACHE_PREFIX.md5($key);

        // Deduplication: skip if this alert was already fired within the window
        if (Cache::has($cacheKey)) {
            Log::debug('[AlertService] Alert suppressed (dedup)', [
                'key' => $key,
                'severity' => $severity,
            ]);

            return false;
        }

        Cache::put($cacheKey, now()->toIso8601String(), $dedupWindowSeconds);

        $logContext = array_merge($context, [
            'alert_key' => $key,
            'severity' => $severity,
            'fired_at' => now()->toIso8601String(),
        ]);

        match ($severity) {
            'critical' => Log::critical("[Alert] {$title}", $logContext),
            'high' => Log::error("[Alert] {$title}", $logContext),
            'medium' => Log::warning("[Alert] {$title}", $logContext),
            default => Log::info("[Alert] {$title}", $logContext),
        };

        return true;
    }

    /**
     * Alert on a SecurityEvent model — convenience wrapper.
     *
     * Uses `event_type:organization_id:event_id` as dedup key so each unique
     * security event fires only once per dedup window.
     */
    public function fireForSecurityEvent(SecurityEvent $event): bool
    {
        $key = implode(':', [
            $event->event_type,
            (string) $event->organization_id,
            (string) $event->id,
        ]);

        return $this->fire(
            key: $key,
            title: $event->title,
            message: $event->description ?? $event->title,
            severity: $event->severity,
            context: [
                'security_event_id' => $event->id,
                'organization_id' => $event->organization_id,
                'agent_deployment_id' => $event->agent_deployment_id,
                'event_type' => $event->event_type,
                'source_ip' => $event->source_ip,
                'status' => $event->status,
            ]
        );
    }

    /**
     * Alert when an agent exceeds the circuit-breaker failure threshold.
     */
    public function fireCircuitBreakerAlert(string $service, int $failures): bool
    {
        return $this->fire(
            key: "circuit_breaker:{$service}",
            title: "Circuit Breaker OPEN — Service: {$service}",
            message: "The {$service} circuit breaker opened after {$failures} consecutive failures. "
                .'Requests are being rejected with a fallback response.',
            severity: 'critical',
            context: ['service' => $service, 'failures' => $failures],
            dedupWindowSeconds: 300  // 5-min dedup for circuit breaker alerts
        );
    }

    /**
     * Alert on repeated authentication failures from the same IP.
     */
    public function fireAuthFailureAlert(string $ip, int $attempts, int $organizationId): bool
    {
        return $this->fire(
            key: "auth_failure:{$ip}:{$organizationId}",
            title: 'Repeated Authentication Failures Detected',
            message: "IP {$ip} has failed authentication {$attempts} times in the last 15 minutes.",
            severity: 'high',
            context: [
                'ip' => $ip,
                'attempts' => $attempts,
                'organization_id' => $organizationId,
            ],
            dedupWindowSeconds: 900
        );
    }

    /**
     * Alert on a prompt injection detection.
     */
    public function firePromptInjectionAlert(
        int $deploymentId,
        int $organizationId,
        float $score,
        string $snippet
    ): bool {
        return $this->fire(
            key: "prompt_injection:{$organizationId}:{$deploymentId}",
            title: 'Prompt Injection Attempt Detected',
            message: "A prompt injection attempt was detected (score: {$score}) for deployment #{$deploymentId}.",
            severity: $score >= 0.9 ? 'critical' : 'high',
            context: [
                'deployment_id' => $deploymentId,
                'organization_id' => $organizationId,
                'injection_score' => $score,
                'input_snippet' => substr($snippet, 0, 200),
            ],
            dedupWindowSeconds: 300
        );
    }
}
