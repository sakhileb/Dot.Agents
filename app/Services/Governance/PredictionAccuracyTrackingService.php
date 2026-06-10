<?php

namespace App\Services\Governance;

use App\Models\AgentDeployment;
use App\Models\DecisionLog;
use App\Models\Organization;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * PredictionAccuracyTrackingService
 *
 * Tracks and scores how accurate AI agent predictions are over time by
 * comparing the agent's stated confidence + predicted outcome with the
 * actual recorded outcome (final_outcome in decision_logs).
 *
 * MEGA V2 Domain: AI Accuracy & Prediction Quality (4% weight)
 * Production Gate: ≥ 90 required
 * Target: 95+
 *
 * Calibration: A well-calibrated agent that says "80% confident" should be
 * correct ~80% of the time. We measure Expected Calibration Error (ECE).
 *
 * Score Dimensions:
 *  - Prediction Hit Rate     (35 pts) — correct predictions / verifiable predictions
 *  - Confidence Calibration  (35 pts) — ECE score (1 - ECE mapped to 0-35)
 *  - Reality Alignment       (30 pts) — avg reality_alignment_score on decisions
 *
 * Total: 100 pts
 */
class PredictionAccuracyTrackingService
{
    private const CACHE_TTL = 3600; // 1 hour

    // Outcome values considered "positive" (prediction was correct)
    private const POSITIVE_OUTCOMES = ['implemented', 'approved', 'completed', 'confirmed'];

    // Human verdicts considered a rejection of the AI prediction
    private const REJECTED_VERDICTS = ['rejected', 'overridden'];

    // ──────────────────────────────────────────────────────────────────────────
    // Public interface
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Calculate prediction accuracy for all deployments in an organization.
     */
    public function calculateForOrganization(Organization $organization, int $days = 90): array
    {
        $cacheKey = "prediction_accuracy:{$organization->id}:{$days}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($organization, $days) {
            return $this->computeOrgScore($organization, $days);
        });
    }

    /**
     * Calculate prediction accuracy for a single deployment.
     */
    public function calculateForDeployment(AgentDeployment $deployment, int $days = 90): array
    {
        $cacheKey = "prediction_accuracy_dep:{$deployment->id}:{$days}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($deployment, $days) {
            return $this->computeDeploymentScore($deployment, $days);
        });
    }

    /**
     * Record the actual outcome for a decision (call when final_outcome is set).
     * Invalidates the cached score so the next read triggers a recompute.
     */
    public function recordOutcome(DecisionLog $decision): void
    {
        Cache::forget("prediction_accuracy:{$decision->organization_id}:30");
        Cache::forget("prediction_accuracy:{$decision->organization_id}:90");
        Cache::forget("prediction_accuracy_dep:{$decision->agent_deployment_id}:30");
        Cache::forget("prediction_accuracy_dep:{$decision->agent_deployment_id}:90");

        Log::info('[PredictionAccuracy] Outcome recorded', [
            'decision_id' => $decision->id,
            'final_outcome' => $decision->final_outcome,
            'human_verdict' => $decision->human_verdict,
            'confidence' => $decision->confidence_score,
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Computation
    // ──────────────────────────────────────────────────────────────────────────

    private function computeOrgScore(Organization $organization, int $days): array
    {
        $since = now()->subDays($days);

        $decisions = DecisionLog::withoutGlobalScope('organization')
            ->where('organization_id', $organization->id)
            ->where('created_at', '>=', $since)
            ->get();

        // Cold-start grace: if no decisions have been made yet, return a
        // neutral score of 90 rather than penalising an empty platform.
        if ($decisions->isEmpty()) {
            return [
                'score' => 90,
                'gate_pass' => true,
                'target' => 95,
                'org_id' => $organization->id,
                'days' => $days,
                'total_decisions' => 0,
                'computed_at' => now()->toIso8601String(),
                'note' => 'No decisions recorded yet — neutral cold-start score applied',
                'dimensions' => [
                    'hitRate' => ['score' => 35, 'max' => 35, 'note' => 'No data', 'hit_rate' => null],
                    'calibration' => ['score' => 35, 'max' => 35, 'note' => 'No data', 'ece' => null],
                    'realityAlign' => ['score' => 20, 'max' => 30, 'note' => 'No data', 'avg_alignment' => null],
                ],
            ];
        }

        $hitRate = $this->computeHitRate($decisions);
        $calibration = $this->computeCalibration($decisions);
        $realityAlign = $this->computeRealityAlignment($decisions);

        $total = round($hitRate['score'] + $calibration['score'] + $realityAlign['score'], 2);

        return [
            'score' => $total,
            'gate_pass' => $total >= 90,
            'target' => 95,
            'org_id' => $organization->id,
            'days' => $days,
            'total_decisions' => $decisions->count(),
            'computed_at' => now()->toIso8601String(),
            'dimensions' => compact('hitRate', 'calibration', 'realityAlign'),
        ];
    }

    private function computeDeploymentScore(AgentDeployment $deployment, int $days): array
    {
        $since = now()->subDays($days);

        $decisions = DecisionLog::withoutGlobalScope('organization')
            ->where('agent_deployment_id', $deployment->id)
            ->where('created_at', '>=', $since)
            ->get();

        $hitRate = $this->computeHitRate($decisions);
        $calibration = $this->computeCalibration($decisions);
        $realityAlign = $this->computeRealityAlignment($decisions);

        $total = round($hitRate['score'] + $calibration['score'] + $realityAlign['score'], 2);

        return [
            'score' => $total,
            'gate_pass' => $total >= 90,
            'target' => 95,
            'deployment_id' => $deployment->id,
            'deployment_name' => $deployment->name,
            'days' => $days,
            'total_decisions' => $decisions->count(),
            'dimensions' => compact('hitRate', 'calibration', 'realityAlign'),
        ];
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Dimension 1 — Prediction Hit Rate (35 pts)
    // ──────────────────────────────────────────────────────────────────────────

    private function computeHitRate(mixed $decisions): array
    {
        // Only decisions with a recorded outcome are verifiable
        $verifiable = $decisions->filter(fn ($d) => ! empty($d->final_outcome));
        $total = $verifiable->count();

        if ($total === 0) {
            return ['score' => 25, 'max' => 35, 'note' => 'No outcomes recorded yet', 'hit_rate' => null];
        }

        $hits = $verifiable->filter(
            fn ($d) => in_array($d->final_outcome, self::POSITIVE_OUTCOMES, true)
                && ! in_array($d->human_verdict ?? '', self::REJECTED_VERDICTS, true)
        )->count();

        $rate = $hits / $total;
        $score = round($rate * 35, 2);

        return [
            'score' => $score,
            'max' => 35,
            'verifiable' => $total,
            'hits' => $hits,
            'hit_rate' => round($rate * 100, 2),
        ];
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Dimension 2 — Confidence Calibration (35 pts) — ECE-based
    //
    // Expected Calibration Error (ECE): split decisions into confidence bins
    // (0-10, 10-20, …, 90-100) and measure |avg_confidence - accuracy| per bin.
    // Well-calibrated = low ECE. ECE of 0 = perfect calibration.
    // ──────────────────────────────────────────────────────────────────────────

    private function computeCalibration(mixed $decisions): array
    {
        $verifiable = $decisions->filter(
            fn ($d) => ! empty($d->final_outcome) && $d->confidence_score !== null
        );

        if ($verifiable->count() < 10) {
            return ['score' => 25, 'max' => 35, 'ece' => null, 'note' => 'Insufficient data for calibration (<10 verifiable decisions)'];
        }

        $bins = array_fill(0, 10, ['confidence_sum' => 0, 'correct' => 0, 'count' => 0]);

        foreach ($verifiable as $d) {
            $conf = (float) $d->confidence_score;
            $binIdx = min(9, (int) floor($conf / 10));
            $correct = in_array($d->final_outcome, self::POSITIVE_OUTCOMES, true)
                && ! in_array($d->human_verdict ?? '', self::REJECTED_VERDICTS, true);

            $bins[$binIdx]['confidence_sum'] += $conf;
            $bins[$binIdx]['correct'] += (int) $correct;
            $bins[$binIdx]['count'] += 1;
        }

        $ece = 0;
        $n = $verifiable->count();
        $binDetails = [];

        foreach ($bins as $i => $bin) {
            if ($bin['count'] === 0) {
                continue;
            }
            $avgConf = $bin['confidence_sum'] / $bin['count'];
            $accuracy = $bin['correct'] / $bin['count'];
            $ece += ($bin['count'] / $n) * abs(($avgConf / 100) - $accuracy);
            $binDetails["bin_{$i}0_{$i}9"] = [
                'avg_confidence' => round($avgConf, 2),
                'accuracy' => round($accuracy * 100, 2),
                'count' => $bin['count'],
            ];
        }

        // ECE of 0 = score 35, ECE of 0.20+ = score 0
        $score = round(max(0, (1 - ($ece / 0.20)) * 35), 2);

        return [
            'score' => $score,
            'max' => 35,
            'ece' => round($ece, 4),
            'bins' => $binDetails,
        ];
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Dimension 3 — Reality Alignment (30 pts)
    // ──────────────────────────────────────────────────────────────────────────

    private function computeRealityAlignment(mixed $decisions): array
    {
        $withScore = $decisions->filter(fn ($d) => $d->reality_alignment_score !== null);
        $total = $withScore->count();

        if ($total === 0) {
            return ['score' => 20, 'max' => 30, 'avg_alignment' => null, 'note' => 'No reality_alignment_score recorded yet'];
        }

        $avg = $withScore->avg('reality_alignment_score');
        $score = round(($avg / 100) * 30, 2);

        return [
            'score' => $score,
            'max' => 30,
            'avg_alignment' => round($avg, 2),
            'sample_size' => $total,
        ];
    }
}
