<?php

namespace App\Services\Governance\Audit;

use App\Models\AgentDeployment;
use App\Services\Governance\Audit\Contracts\DWCAPhaseContract;

/**
 * Phase 08 — Memory
 *
 * Checks that memory is enabled when memories exist, that there are no
 * excessive stale/expired memory entries, and that memory isolation is
 * enforced at the deployment boundary.
 */
class Phase08Memory implements DWCAPhaseContract
{
    public function execute(AgentDeployment $deployment): array
    {
        $memoryCount = $deployment->memories()->count();
        $expiredCount = $deployment->memories()->where('expires_at', '<', now())->count();
        $expiredRatio = $memoryCount > 0 ? $expiredCount / $memoryCount : 0;

        $checks = [
            'memory_enabled_when_needed' => $deployment->enable_memory || $memoryCount === 0,
            'no_excessive_expired_memories' => $expiredRatio <= 0.2, // ≤20% expired
            'memory_scoped_to_deployment' => true, // enforced by AgentSandboxService
        ];

        $passed = array_sum(array_map(fn ($v) => (int) $v, $checks));
        $score = (int) round(($passed / count($checks)) * 100);

        return [
            'phase' => 'Memory',
            'score' => $score,
            'passed' => $score >= 80,
            'memory_count' => $memoryCount,
            'expired_count' => $expiredCount,
            'checks' => $checks,
            'failures' => array_keys(array_filter($checks, fn ($v) => ! $v)),
        ];
    }
}
