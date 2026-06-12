<?php

namespace App\Services\Governance\Audit;

use App\Models\AgentDeployment;
use App\Services\Governance\Audit\Contracts\DWCAPhaseContract;

/**
 * Phase 13 — Scorecard
 *
 * Checks whether a scorecard exists and whether the key health dimensions
 * (overall health, accuracy, compliance, reliability) meet the ≥70 threshold.
 */
class Phase13Scorecard implements DWCAPhaseContract
{
    public function execute(AgentDeployment $deployment): array
    {
        $latestScorecard = $deployment->latestScorecard;

        if (! $latestScorecard) {
            return [
                'phase' => 'Scorecard',
                'score' => 0,
                'passed' => false,
                'checks' => ['scorecard_exists' => false],
                'failures' => ['scorecard_exists'],
                'recommendation' => 'Generate a scorecard by running GenerateAgentScorecard job.',
            ];
        }

        $checks = [
            'scorecard_exists' => true,
            'overall_health_above_70' => $latestScorecard->overall_health_score >= 70,
            'accuracy_above_70' => $latestScorecard->accuracy_score >= 70,
            'compliance_above_70' => $latestScorecard->compliance_score >= 70,
            'reliability_above_70' => $latestScorecard->reliability_score >= 70,
        ];

        $passed = array_sum(array_map(fn ($v) => (int) $v, $checks));
        $score = (int) round(($passed / count($checks)) * 100);

        return [
            'phase' => 'Scorecard',
            'score' => $score,
            'passed' => $score >= 80,
            'scorecard_scores' => [
                'overall_health' => $latestScorecard->overall_health_score,
                'accuracy' => $latestScorecard->accuracy_score,
                'compliance' => $latestScorecard->compliance_score,
                'reliability' => $latestScorecard->reliability_score,
                'trustworthiness' => $latestScorecard->trustworthiness_score,
            ],
            'checks' => $checks,
            'failures' => array_keys(array_filter($checks, fn ($v) => ! $v)),
        ];
    }
}
