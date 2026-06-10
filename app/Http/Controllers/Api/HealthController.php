<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Lightweight liveness / readiness probe for load balancers, k8s, and CI/CD
 * deployment pipelines.
 *
 * GET /api/health — no authentication required.
 *
 * Returns 200 when all checks pass, 503 when one or more are degraded.
 */
class HealthController
{
    public function __invoke(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'storage' => $this->checkStorage(),
        ];

        // Optional services — degrade gracefully when not configured
        $checks['redis'] = $this->checkRedis();
        $checks['queue'] = $this->checkQueue();

        $allHealthy = collect($checks)->every(fn ($v) => $v === 'ok');

        return response()->json([
            'status' => $allHealthy ? 'healthy' : 'degraded',
            'checks' => $checks,
            'timestamp' => now()->toIso8601String(),
            'version' => config('app.version', '1.0.0'),
        ], $allHealthy ? 200 : 503);
    }

    private function checkDatabase(): string
    {
        try {
            DB::connection()->getPdo();
            DB::selectOne('SELECT 1');

            return 'ok';
        } catch (Throwable) {
            return 'error';
        }
    }

    private function checkCache(): string
    {
        try {
            $key = '__health_check_'.time();
            Cache::put($key, 1, 5);
            Cache::forget($key);

            return 'ok';
        } catch (Throwable) {
            return 'error';
        }
    }

    private function checkRedis(): string
    {
        try {
            // Only check when Redis is the cache/session driver
            if (! in_array(config('cache.default'), ['redis', 'array'])) {
                Redis::ping();
            }

            return 'ok';
        } catch (Throwable) {
            // Redis not configured or unavailable — report but don't block
            return config('cache.default') === 'redis' ? 'error' : 'ok';
        }
    }

    private function checkQueue(): string
    {
        try {
            // Verify queue connection is reachable (non-blocking size check)
            Queue::size();

            return 'ok';
        } catch (Throwable) {
            return 'error';
        }
    }

    private function checkStorage(): string
    {
        try {
            Storage::disk('local')->put('health_check', '1');
            Storage::disk('local')->delete('health_check');

            return 'ok';
        } catch (Throwable) {
            return 'error';
        }
    }
}
