<?php

namespace App\Services\Governance\DataTrust;

use App\Models\AgentMemory;
use App\Models\DecisionLog;

/**
 * DataGovernanceScorer
 *
 * Computes the two governance dimensions of the Data Trust Score:
 *  - Memory Quality     (15 pts)
 *  - Decision Quality   (15 pts)
 *
 * Also generates human-readable recommendations from dimension results.
 *
 * This class is purely computational — no caching, no side-effects.
 * Extracted from DataTrustScoreService.
 */
class DataGovernanceScorer
{
    /**
     * Score memory quality: verified memories and average importance.
     */
    public function scoreMemoryQuality(int $orgId): array
    {
        $total = AgentMemory::withoutGlobalScope('organization')
            ->where('organization_id', $orgId)
            ->where('is_active', true)->count();

        $verified = AgentMemory::withoutGlobalScope('organization')
            ->where('organization_id', $orgId)
            ->where('is_active', true)
            ->where('is_verified', true)->count();

        $avgImportance = AgentMemory::withoutGlobalScope('organization')
            ->where('organization_id', $orgId)
            ->where('is_active', true)
            ->avg('importance_score') ?? 50;

        $verifiedRate = $total > 0 ? $verified / $total : 0;
        $importanceNorm = min(1, max(0, ($avgImportance - 30) / 70));
        $combined = ($verifiedRate * 0.6) + ($importanceNorm * 0.4);

        // Cold-start grace: no memories yet → neutral baseline of 10/15
        $score = $total === 0 ? 10.0 : round($combined * 15, 2);

        return [
            'score' => $score,
            'max' => 15,
            'total_memories' => $total,
            'verified_memories' => $verified,
            'verified_rate' => round($verifiedRate * 100, 2),
            'avg_importance' => round($avgImportance, 2),
        ];
    }

    /**
     * Score decision quality: outcome tracking and delusion risk.
     */
    public function scoreDecisionQuality(int $orgId): array
    {
        $total = DecisionLog::withoutGlobalScope('organization')
            ->where('organization_id', $orgId)->count();

        $withOutcome = DecisionLog::withoutGlobalScope('organization')
            ->where('organization_id', $orgId)
            ->whereNotNull('final_outcome')->count();

        $highDelusion = DecisionLog::withoutGlobalScope('organization')
            ->where('organization_id', $orgId)
            ->where('delusion_risk_score', '>=', 60)->count();

        $outcomeRate = $total > 0 ? $withOutcome / $total : 0;
        $delusionRate = $total > 0 ? $highDelusion / $total : 0;
        $qualityIndex = ($outcomeRate * 0.5) + ((1 - $delusionRate) * 0.5);

        // Cold-start grace: no decisions yet → neutral baseline of 10/15
        $score = $total === 0 ? 10.0 : round($qualityIndex * 15, 2);

        return [
            'score' => $score,
            'max' => 15,
            'total_decisions' => $total,
            'with_outcome' => $withOutcome,
            'high_delusion' => $highDelusion,
            'outcome_rate' => round($outcomeRate * 100, 2),
            'delusion_rate' => round($delusionRate * 100, 2),
        ];
    }

    /**
     * Generate actionable recommendations from dimension results.
     *
     * @param  array  ...$dimensions  Variable number of dimension result arrays
     * @return string[]
     */
    public function generateRecommendations(array ...$dimensions): array
    {
        $recs = [];

        foreach ($dimensions as $dim) {
            if (isset($dim['completeness_rate']) && $dim['completeness_rate'] < 95) {
                $recs[] = "Improve task completeness: {$dim['completeness_rate']}% of completed tasks have result_summary and confidence_score. Target: 95%.";
            }
            if (isset($dim['orphan_rate']) && $dim['orphan_rate'] > 2) {
                $recs[] = "Fix data integrity: {$dim['orphan_rate']}% orphan records detected. Run data reconciliation.";
            }
            if (isset($dim['freshness_rate']) && $dim['freshness_rate'] < 80) {
                $recs[] = "Improve data freshness: {$dim['freshness_rate']}% fresh. Review and update stale knowledge articles and agent memories.";
            }
            if (isset($dim['verified_rate']) && $dim['verified_rate'] < 70) {
                $recs[] = "Increase memory verification: only {$dim['verified_rate']}% of agent memories are verified. Target: 70%.";
            }
            if (isset($dim['outcome_rate']) && $dim['outcome_rate'] < 60) {
                $recs[] = "Improve decision outcome tracking: only {$dim['outcome_rate']}% of decisions have recorded outcomes. Target: 60%.";
            }
        }

        return $recs;
    }
}
