<?php

namespace App\Services\Governance\Scorecard;

/**
 * ScorecardGateEvaluator
 *
 * Evaluates the MEGA V2 production gates against computed domain scores.
 *
 * Gates auto-fail a scorecard regardless of the overall numeric score:
 *  - Critical security findings must be zero
 *  - Reality Alignment ≥ 85
 *  - Data Trust ≥ 90
 *  - AI Reliability ≥ 90
 *  - Agent Reliability ≥ 90
 *  - Observability ≥ 85 (prod) / ≥ 65 (dev)
 *
 * This class is purely computational — no I/O, no cache, no side-effects.
 */
class ScorecardGateEvaluator
{
    /**
     * Evaluate all production gates and return a gates array.
     * The 'all_pass' key indicates whether every gate passed.
     *
     * @return array<string, mixed>
     */
    public function evaluate(
        float $finalScore,
        array $dataTrust,
        array $agentReliability,
        array $predictionAcc,
        array $observability,
        array $disResult
    ): array {
        $criticalSecurityFindings = ($disResult['quarantined'] ?? 0) + ($disResult['critical'] ?? 0);
        $realityAlignment = $predictionAcc['dimensions']['realityAlign']['avg_alignment'] ?? 100;

        // When there are no decisions yet, prediction accuracy uses safe defaults.
        // Don't block a fresh platform that hasn't collected data.
        $predictionScore = $predictionAcc['score'];
        $noDecisionData = ($predictionAcc['total_decisions'] ?? 0) === 0;

        $gates = [
            'no_critical_security_findings' => [
                'pass' => $criticalSecurityFindings === 0,
                'value' => $criticalSecurityFindings,
                'threshold' => 0,
                'label' => 'Critical Security Findings = 0',
            ],
            'reality_alignment_ge_85' => [
                'pass' => ($realityAlignment ?? 100) >= 85,
                'value' => $realityAlignment,
                'threshold' => 85,
                'label' => 'Reality Alignment ≥ 85',
            ],
            'data_trust_ge_90' => [
                'pass' => $dataTrust['score'] >= 90,
                'value' => $dataTrust['score'],
                'threshold' => 90,
                'label' => 'Data Trust ≥ 90',
            ],
            'ai_reliability_ge_90' => [
                'pass' => $noDecisionData || $predictionScore >= 90,
                'value' => $predictionScore,
                'threshold' => 90,
                'label' => 'AI Reliability ≥ 90',
                'note' => $noDecisionData ? 'No decision data yet — grace pass' : null,
            ],
            'agent_reliability_ge_90' => [
                'pass' => $agentReliability['score'] >= 90,
                'value' => $agentReliability['score'],
                'threshold' => 90,
                'label' => 'Agent Reliability ≥ 90',
            ],
            'observability_ge_85' => [
                'pass' => app()->isProduction()
                    ? $observability['score'] >= 85
                    : $observability['score'] >= 65,
                'value' => $observability['score'],
                'threshold' => app()->isProduction() ? 85 : 65,
                'label' => 'Observability ≥ 85 (prod) / ≥ 65 (dev)',
                'action' => $observability['score'] < 85 ? 'Set SENTRY_LARAVEL_DSN in .env to gain +20 pts' : null,
            ],
        ];

        $gates['all_pass'] = collect($gates)->every(fn ($g) => is_array($g) ? ($g['pass'] ?? true) : true);

        return $gates;
    }
}
