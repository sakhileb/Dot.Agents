<?php

namespace App\Services\Governance\Audit;

use App\Models\AgentDeployment;
use App\Models\AgentTask;
use App\Services\Governance\Audit\Contracts\DWCAPhaseContract;

/**
 * Phase 12 — Performance
 *
 * Measures task latency, per-task cost, and token budget compliance over
 * the past 7 days of completed tasks.
 */
class Phase12Performance implements DWCAPhaseContract
{
    public function execute(AgentDeployment $deployment): array
    {
        $recentTasks = AgentTask::where('agent_deployment_id', $deployment->id)
            ->where('status', 'completed')
            ->where('created_at', '>=', now()->subDays(7))
            ->get();

        $avgLatency = $recentTasks->avg('actual_duration_minutes');
        $avgCost = $recentTasks->avg('cost') ?? 0;
        $tokenBudgetViolations = $recentTasks->where('token_count', '>', 32000)->count();

        $checks = [
            'avg_latency_under_5min' => ($avgLatency ?? 0) <= 5,
            'no_token_budget_violations' => $tokenBudgetViolations === 0,
            'cost_per_task_reasonable' => $avgCost <= 1.0, // under $1 per task
        ];

        $passed = array_sum(array_map(fn ($v) => (int) $v, $checks));
        $score = (int) round(($passed / count($checks)) * 100);

        return [
            'phase' => 'Performance',
            'score' => $score,
            'passed' => $score >= 80,
            'metrics' => [
                'avg_latency_minutes' => round($avgLatency ?? 0, 2),
                'avg_cost_per_task_usd' => round($avgCost, 4),
                'token_budget_violations' => $tokenBudgetViolations,
            ],
            'checks' => $checks,
            'failures' => array_keys(array_filter($checks, fn ($v) => ! $v)),
        ];
    }
}
