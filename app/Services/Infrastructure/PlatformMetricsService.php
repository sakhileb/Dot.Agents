<?php

namespace App\Services\Infrastructure;

use App\Models\AgentDeployment;
use App\Models\AgentTask;
use App\Models\SecurityEvent;
use App\Services\Resilience\CircuitBreakerService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

/**
 * Platform Metrics Service
 *
 * Provides the real-time and aggregated metrics that power the Executive
 * Operations Dashboard. All heavy queries are cached for 60 seconds to
 * avoid N+1 on every dashboard refresh.
 */
class PlatformMetricsService
{
    private const CACHE_TTL = 60; // seconds

    public function __construct(
        private readonly CircuitBreakerService $circuitBreaker,
    ) {}

    /**
     * Build the full operations dashboard snapshot for an organization.
     */
    public function getOperationsSnapshot(int $organizationId): array
    {
        return Cache::remember("ops_snapshot_{$organizationId}", self::CACHE_TTL, function () use ($organizationId) {
            return [
                'agent_executions' => $this->getAgentExecutions($organizationId),
                'failure_rate' => $this->getFailureRate($organizationId),
                'avg_response_time_ms' => $this->getAvgResponseTime($organizationId),
                'queue_depth' => $this->getQueueDepth(),
                'security_events' => $this->getSecurityEventStats($organizationId),
                'circuit_breakers' => $this->getCircuitBreakerStatuses(),
                'active_deployments' => $this->getActiveDeploymentCount($organizationId),
                'generated_at' => now()->toIso8601String(),
            ];
        });
    }

    /**
     * Agent executions — last 24h completed + failed tasks.
     */
    public function getAgentExecutions(int $organizationId): array
    {
        return Cache::remember("ops_executions_{$organizationId}", self::CACHE_TTL, function () use ($organizationId) {
            $query = AgentTask::where('organization_id', $organizationId)
                ->where('created_at', '>=', now()->subDay());

            return [
                'total_24h' => $query->count(),
                'completed' => (clone $query)->where('status', 'completed')->count(),
                'failed' => (clone $query)->where('status', 'failed')->count(),
                'awaiting_approval' => (clone $query)->where('status', 'awaiting_approval')->count(),
                'in_progress' => (clone $query)->where('status', 'in_progress')->count(),
            ];
        });
    }

    /**
     * Task failure rate (%) over last 24h.
     */
    public function getFailureRate(int $organizationId): float
    {
        $executions = $this->getAgentExecutions($organizationId);
        $total = $executions['total_24h'];

        if ($total === 0) {
            return 0.0;
        }

        return round(($executions['failed'] / $total) * 100, 1);
    }

    /**
     * Average AI response latency in ms over last 24h completed tasks.
     */
    public function getAvgResponseTime(int $organizationId): float
    {
        return Cache::remember("ops_avg_response_{$organizationId}", self::CACHE_TTL, function () use ($organizationId) {
            $avg = AgentTask::where('organization_id', $organizationId)
                ->where('status', 'completed')
                ->where('created_at', '>=', now()->subDay())
                ->whereNotNull('actual_duration_minutes')
                ->avg('actual_duration_minutes');

            return round(($avg ?? 0) * 60000, 0); // convert minutes to ms
        });
    }

    /**
     * Queue depth — pending jobs across all named queues.
     * Uses DB-based queue driver (works with database driver).
     */
    public function getQueueDepth(): array
    {
        return Cache::remember('ops_queue_depth', 30, function () {
            try {
                $queues = ['default', 'governance', 'notifications', 'agents', 'billing', 'reports', 'security'];
                $depths = [];

                foreach ($queues as $queue) {
                    $depths[$queue] = DB::table('jobs')
                        ->where('queue', $queue)
                        ->whereNull('reserved_at')
                        ->count();
                }

                $depths['_total'] = array_sum($depths);
                $depths['_failed'] = DB::table('failed_jobs')->count();

                return $depths;
            } catch (\Throwable) {
                return ['_total' => 0, '_failed' => 0];
            }
        });
    }

    /**
     * Security event counts and prompt injection attempts.
     */
    public function getSecurityEventStats(int $organizationId): array
    {
        return Cache::remember("ops_security_{$organizationId}", self::CACHE_TTL, function () use ($organizationId) {
            $events = SecurityEvent::where('organization_id', $organizationId);

            return [
                'open_critical' => (clone $events)->where('severity', 'critical')->where('status', 'open')->count(),
                'open_high' => (clone $events)->where('severity', 'high')->where('status', 'open')->count(),
                'last_24h' => (clone $events)->where('created_at', '>=', now()->subDay())->count(),
                'prompt_injections_24h' => (clone $events)
                    ->where('event_type', 'prompt_injection')
                    ->where('created_at', '>=', now()->subDay())
                    ->count(),
                'auto_remediated' => (clone $events)->where('auto_remediated', true)->count(),
            ];
        });
    }

    /**
     * Circuit breaker statuses for all known external services.
     */
    public function getCircuitBreakerStatuses(): array
    {
        $services = ['ai_inference_openai', 'ai_inference_anthropic', 'ai_inference_google', 'ai_inference_ollama'];
        $statuses = [];

        foreach ($services as $service) {
            $statuses[$service] = $this->circuitBreaker->status($service);
        }

        return $statuses;
    }

    /**
     * Count of active agent deployments.
     */
    public function getActiveDeploymentCount(int $organizationId): int
    {
        return Cache::remember("ops_active_deployments_{$organizationId}", self::CACHE_TTL, fn () => AgentDeployment::where('organization_id', $organizationId)
            ->where('status', 'active')
            ->count()
        );
    }

    /**
     * Invalidate all ops metrics for an organization.
     */
    public function invalidate(int $organizationId): void
    {
        $keys = [
            "ops_snapshot_{$organizationId}",
            "ops_executions_{$organizationId}",
            "ops_avg_response_{$organizationId}",
            "ops_security_{$organizationId}",
            "ops_active_deployments_{$organizationId}",
            'ops_queue_depth',
        ];

        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }
}
