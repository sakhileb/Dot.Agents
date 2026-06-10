<?php

namespace App\Http\Controllers;

use App\Services\Infrastructure\HealthCheckService;
use Illuminate\Http\JsonResponse;

/**
 * Health Check Controller
 *
 * Routes:
 *   GET /health            — Public liveness check (no auth required)
 *   GET /health/detailed   — Full infrastructure health (auth required)
 */
class HealthCheckController extends Controller
{
    public function __construct(
        private readonly HealthCheckService $healthCheck
    ) {}

    /**
     * Basic liveness check — returns 200 if app is alive.
     * Used by load balancers and Kubernetes probes.
     */
    public function ping(): JsonResponse
    {
        $alive = $this->healthCheck->alive();

        return response()->json(
            ['status' => $alive ? 'ok' : 'error'],
            $alive ? 200 : 503
        );
    }

    /**
     * Detailed health report — database, cache, queue, storage, agent runtime.
     * Requires authentication (internal monitoring use only).
     */
    public function detailed(): JsonResponse
    {
        $report = $this->healthCheck->check();

        $httpStatus = match ($report['status']) {
            'healthy' => 200,
            'degraded' => 200,  // degraded still serves traffic
            default => 503,     // unhealthy returns 503
        };

        return response()->json($report, $httpStatus);
    }
}
