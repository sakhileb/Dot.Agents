<?php

namespace App\Services\Governance\Audit;

use App\Models\AgentDeployment;
use App\Models\AgentTask;
use App\Models\DecisionLog;
use App\Services\Governance\Audit\Contracts\DWCAPhaseContract;

/**
 * Phase 04 — Agent Quality
 *
 * Measures real-world task completion rate, average confidence, and
 * hallucination/delusion metrics over the past 30 days.
 */
class Phase04AgentQuality implements DWCAPhaseContract
{
    public function execute(AgentDeployment $deployment): array
    {
        $recentTasks = AgentTask::where('agent_deployment_id', $deployment->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->get();

        $recentDecisions = DecisionLog::where('agent_deployment_id', $deployment->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->get();

        $totalTasks = $recentTasks->count();
        $completedTasks = $recentTasks->where('status', 'completed')->count();
        $taskCompletionRate = $totalTasks > 0 ? ($completedTasks / $totalTasks) * 100 : 100;

        $avgConfidence = $recentTasks->avg('confidence_score') ?? 75;
        $avgDelusion = $recentDecisions->avg('delusion_risk_score') ?? 0;

        $inputHashCoverage = $recentDecisions->count() > 0
            ? ($recentDecisions->whereNotNull('input_hash')->count() / $recentDecisions->count()) * 100
            : 0;

        $hallucinationRate = $totalTasks > 0
            ? ($recentTasks->where('delusion_risk_score', '>=', 60)->count() / $totalTasks) * 100
            : 0;

        $checks = [
            'task_completion_rate_above_80' => $taskCompletionRate >= 80,
            'avg_confidence_above_70' => $avgConfidence >= 70,
            'hallucination_rate_below_5_percent' => $hallucinationRate <= 5,
            'delusion_risk_below_40' => $avgDelusion <= 40,
            'decision_logs_have_input_hash' => $inputHashCoverage >= 80 || $recentDecisions->isEmpty(),
        ];

        $passed = array_sum(array_map(fn ($v) => (int) $v, $checks));
        $score = (int) round(($passed / count($checks)) * 100);

        return [
            'phase' => 'Agent Quality',
            'score' => $score,
            'passed' => $score >= 80,
            'metrics' => [
                'task_completion_rate' => round($taskCompletionRate, 1),
                'avg_confidence_score' => round($avgConfidence, 1),
                'hallucination_rate_percent' => round($hallucinationRate, 2),
                'avg_delusion_risk' => round($avgDelusion, 1),
                'input_hash_coverage_percent' => round($inputHashCoverage, 1),
            ],
            'checks' => $checks,
            'failures' => array_keys(array_filter($checks, fn ($v) => ! $v)),
        ];
    }
}
