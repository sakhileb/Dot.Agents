<?php

namespace App\Skills\Governance;

use App\Skills\BaseSkill;
use App\Skills\DTOs\SkillResult;

/**
 * Confidence Scoring Skill (Layer 4 — Self-Governance)
 *
 * Analyses a task output and adjusts the raw confidence score based on:
 *   – Evidence quality and quantity
 *   – Uncertainty language density
 *   – Output completeness
 *   – Assumption count penalty
 *   – Source specificity
 *
 * Returns an adjusted confidence score and flags any uncertainty concerns.
 */
class ConfidenceScoringSkill extends BaseSkill
{
    public function key(): string
    {
        return 'confidence-scoring';
    }

    public function layer(): string
    {
        return 'governance';
    }

    public function execute(array $input, array $context = []): SkillResult
    {
        $output = $input['output'] ?? [];
        $rawConfidence = (float) ($output['confidence'] ?? $input['initial_confidence'] ?? 60.0);

        $adjustments = [
            'evidence_quality' => $this->scoreEvidenceQuality($output),
            'uncertainty_flags' => $this->detectUncertaintyFlags($output),
            'completeness' => $this->scoreOutputCompleteness($output),
            'assumption_penalty' => $this->penalizeExcessiveAssumptions($output),
            'source_specificity' => $this->scoreSourceSpecificity($output),
        ];

        $totalAdjustment = (float) array_sum($adjustments);
        $adjustedScore = $this->clamp($rawConfidence + $totalAdjustment);
        $requiresReview = $adjustedScore < 65.0;

        $findings = [];
        if ($requiresReview) {
            $findings[] = "Adjusted confidence ({$adjustedScore}%) is below review threshold (65%)";
        }
        if ($adjustments['uncertainty_flags'] < -10) {
            $findings[] = 'Output contains significant uncertainty language — recommend verification';
        }
        if ($adjustments['assumption_penalty'] < -5) {
            $findings[] = 'Excessive assumptions detected — validate each before acting on output';
        }

        return SkillResult::completed(
            [
                'raw_confidence' => $rawConfidence,
                'adjusted_confidence' => round($adjustedScore, 1),
                'adjustment' => round($totalAdjustment, 1),
                'breakdown' => $adjustments,
                'requires_review' => $requiresReview,
            ],
            $adjustedScore,
            $findings,
            $requiresReview ? ['Request human review before acting on this output'] : []
        );
    }

    // ── Adjustment factors ───────────────────────────────

    /** +3 per evidence item, capped at +15 */
    private function scoreEvidenceQuality(array $output): float
    {
        $count = count($output['evidence'] ?? []);

        return min(15.0, $count * 3.0);
    }

    /** Penalty for dense uncertainty language */
    private function detectUncertaintyFlags(array $output): float
    {
        $content = strtolower($this->stringify($output));
        $flags = [
            'may', 'might', 'possibly', 'perhaps', 'unclear', 'uncertain',
            'not sure', 'could be', 'unsure', 'probably', 'i think',
            'seems like', 'appears to', 'might be',
        ];

        $count = $this->countKeywords($content, $flags);

        return $count > 3 ? $this->clamp(-($count * 2.5), -30.0, 0.0) : 0.0;
    }

    /** +5 when output has summary + result + reasoning */
    private function scoreOutputCompleteness(array $output): float
    {
        $hasAll = isset($output['summary'], $output['result'], $output['reasoning']);

        return $hasAll ? 5.0 : -10.0;
    }

    /** -2 per assumption over 5 */
    private function penalizeExcessiveAssumptions(array $output): float
    {
        $count = count($output['assumptions'] ?? []);

        return $count > 5 ? -(($count - 5) * 2.0) : 0.0;
    }

    /** +5 when evidence includes URLs / document references */
    private function scoreSourceSpecificity(array $output): float
    {
        $evidence = $this->stringify($output['evidence'] ?? []);
        $hasSpecific = preg_match('/(https?:\/\/|doi:|ISBN:|report|study|survey)/i', $evidence);

        return $hasSpecific ? 5.0 : 0.0;
    }
}
