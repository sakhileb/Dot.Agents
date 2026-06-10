<?php

namespace App\Actions\Skills;

use App\Models\AgentSkillScore;

class RecordSkillScoreAction
{
    /**
     * Upsert monthly skill performance scores for a deployment.
     * Called after each execution completes.
     */
    public function execute(
        int $skillId,
        int $deploymentId,
        int $organizationId,
        string $executionStatus,  // completed|failed|blocked|skipped
        ?float $confidence = null,
        ?int $durationMs = null,
    ): void {
        $period = now()->format('Y-m');

        $score = AgentSkillScore::firstOrCreate(
            [
                'skill_id' => $skillId,
                'agent_deployment_id' => $deploymentId,
                'organization_id' => $organizationId,
                'period' => $period,
            ],
            [
                'total_executions' => 0,
                'successful_executions' => 0,
                'failed_executions' => 0,
                'blocked_executions' => 0,
            ]
        );

        // Increment counters
        $score->increment('total_executions');

        match ($executionStatus) {
            'completed' => $score->increment('successful_executions'),
            'failed' => $score->increment('failed_executions'),
            'skipped' => $score->increment('blocked_executions'),
            default => null,
        };

        // Refresh for recalculation
        $score->refresh();

        $updates = [];

        // Recalculate success rate
        if ($score->total_executions > 0) {
            $updates['success_rate'] = round(
                ($score->successful_executions / $score->total_executions) * 100,
                2
            );
        }

        // Update running average confidence
        if ($confidence !== null) {
            $updates['avg_confidence'] = $score->avg_confidence
                ? round(($score->avg_confidence + $confidence) / 2, 2)
                : $confidence;
        }

        // Update running average duration
        if ($durationMs !== null) {
            $updates['avg_duration_ms'] = $score->avg_duration_ms
                ? round(($score->avg_duration_ms + $durationMs) / 2, 2)
                : $durationMs;
        }

        if (! empty($updates)) {
            $score->update($updates);
        }
    }
}
