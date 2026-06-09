<?php

namespace App\Services\Governance;

use App\Models\AgentDeployment;
use App\Models\AgentScorecard;
use Illuminate\Support\Facades\Cache;

/**
 * Computes a weighted, trend-aware reputation score for an agent deployment.
 *
 * Reputation aggregates multiple historical scorecard periods with exponential
 * recency weighting — recent performance counts more than older periods.
 * The score includes drift detection to flag rapidly degrading agents.
 */
class AgentReputationService
{
    private const CACHE_TTL = 3600; // 1 hour

    private const DIMENSION_WEIGHTS = [
        'accuracy_score' => 0.25,
        'reliability_score' => 0.20,
        'compliance_score' => 0.20,
        'productivity_score' => 0.15,
        'safety_score' => 0.12,
        'user_satisfaction_score' => 0.08,
    ];

    private const REPUTATION_TIERS = [
        'elite' => 95,
        'trusted' => 85,
        'satisfactory' => 70,
        'probation' => 50,
        'suspended' => 0,
    ];

    /**
     * Calculate the current reputation score for a deployment.
     * Cached for 1 hour per deployment to avoid repeated aggregation.
     */
    public function getReputationScore(AgentDeployment $deployment): array
    {
        return Cache::remember(
            "agent_reputation_{$deployment->id}",
            self::CACHE_TTL,
            fn () => $this->computeReputation($deployment)
        );
    }

    /**
     * Invalidate cached reputation for a deployment (call after scorecard update).
     */
    public function invalidateCache(AgentDeployment $deployment): void
    {
        Cache::forget("agent_reputation_{$deployment->id}");
    }

    /**
     * Return the reputation tier label for a given score.
     */
    public function getTier(float $score): string
    {
        foreach (self::REPUTATION_TIERS as $tier => $threshold) {
            if ($score >= $threshold) {
                return $tier;
            }
        }

        return 'suspended';
    }

    /**
     * Detect if the agent's reputation is trending downward.
     * Returns true if the last 3 periods show a consistent decline.
     */
    public function isDrifting(AgentDeployment $deployment): bool
    {
        $recent = AgentScorecard::where('agent_deployment_id', $deployment->id)
            ->orderBy('period_end', 'desc')
            ->limit(3)
            ->pluck('overall_health_score')
            ->toArray();

        if (count($recent) < 3) {
            return false;
        }

        // Three consecutive declining periods = drift
        return $recent[0] < $recent[1] && $recent[1] < $recent[2];
    }

    private function computeReputation(AgentDeployment $deployment): array
    {
        $scorecards = AgentScorecard::where('agent_deployment_id', $deployment->id)
            ->orderBy('period_end', 'desc')
            ->limit(6)
            ->get();

        if ($scorecards->isEmpty()) {
            return $this->defaultReputation($deployment);
        }

        // Exponential recency weighting: most recent = 2x, oldest = 1x
        $totalWeight = 0;
        $weightedSum = 0;
        $dimensionSums = array_fill_keys(array_keys(self::DIMENSION_WEIGHTS), 0.0);
        $dimensionWeights = array_fill_keys(array_keys(self::DIMENSION_WEIGHTS), 0.0);

        foreach ($scorecards as $index => $scorecard) {
            $recencyWeight = 1 / ($index + 0.5); // Higher weight for recent periods

            $totalWeight += $recencyWeight;
            $weightedSum += ($scorecard->overall_health_score ?? 0) * $recencyWeight;

            foreach (self::DIMENSION_WEIGHTS as $dimension => $dimWeight) {
                $value = $scorecard->{$dimension} ?? 0;
                $dimensionSums[$dimension] += $value * $recencyWeight;
                $dimensionWeights[$dimension] += $recencyWeight;
            }
        }

        $reputationScore = $totalWeight > 0 ? round($weightedSum / $totalWeight, 2) : 0.0;

        // Calculate per-dimension averages
        $dimensions = [];
        foreach (self::DIMENSION_WEIGHTS as $dimension => $weight) {
            $dimWeight = $dimensionWeights[$dimension];
            $dimensions[$dimension] = $dimWeight > 0
                ? round($dimensionSums[$dimension] / $dimWeight, 2)
                : 0.0;
        }

        $isDrifting = $this->isDrifting($deployment);
        $tier = $this->getTier($reputationScore);

        // Apply drift penalty (reduce score by up to 10 points if drifting)
        if ($isDrifting) {
            $reputationScore = max(0, $reputationScore - 10);
            $tier = $this->getTier($reputationScore);
        }

        return [
            'score' => $reputationScore,
            'tier' => $tier,
            'is_drifting' => $isDrifting,
            'periods_analyzed' => $scorecards->count(),
            'dimensions' => $dimensions,
            'computed_at' => now()->toIso8601String(),
        ];
    }

    private function defaultReputation(AgentDeployment $deployment): array
    {
        return [
            'score' => 75.0,
            'tier' => 'satisfactory',
            'is_drifting' => false,
            'periods_analyzed' => 0,
            'dimensions' => array_fill_keys(array_keys(self::DIMENSION_WEIGHTS), 75.0),
            'computed_at' => now()->toIso8601String(),
        ];
    }
}
