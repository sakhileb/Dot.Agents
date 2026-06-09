<?php

namespace App\Skills\Governance;

use App\Skills\BaseSkill;
use App\Skills\DTOs\SkillResult;

/**
 * Self-Verification Skill (Layer 4 — Self-Governance)
 *
 * Inspects a completed task output and flags issues before it is submitted.
 * Checks: summary present, confidence declared, evidence cited,
 * assumptions declared, and absence of contradictory language clusters.
 */
class SelfVerificationSkill extends BaseSkill
{
    public function key(): string
    {
        return 'self-verification';
    }

    public function layer(): string
    {
        return 'governance';
    }

    public function execute(array $input, array $context = []): SkillResult
    {
        $output = $input['output'] ?? [];
        $task = $input['task_description'] ?? '';

        $checks = [
            'has_summary' => $this->checkHasSummary($output),
            'confidence_declared' => $this->checkConfidenceDeclared($output),
            'no_contradiction' => $this->checkNoContradiction($output),
            'evidence_present' => $this->checkEvidencePresent($output),
            'assumptions_declared' => $this->checkAssumptionsDeclared($output),
            'risks_acknowledged' => $this->checkRisksAcknowledged($output),
        ];

        $passed = array_filter($checks, fn ($c) => $c['passed']);
        $failed = array_filter($checks, fn ($c) => ! $c['passed']);
        $score = $this->clamp(count($passed) / max(1, count($checks)) * 100);
        $findings = array_values(array_map(
            fn ($c) => $c['issue'],
            array_filter($failed, fn ($c) => isset($c['issue']))
        ));

        return SkillResult::completed(
            [
                'verification_score' => round($score, 1),
                'verified' => $score >= 80,
                'checks' => $checks,
                'passed_count' => count($passed),
                'failed_count' => count($failed),
            ],
            $score,
            $findings,
            $score < 80 ? ['Address failed verification checks before submitting output'] : []
        );
    }

    // ── Individual checks ────────────────────────────────

    private function checkHasSummary(array $output): array
    {
        $summary = $output['summary'] ?? '';
        $passed = is_string($summary) && strlen($summary) >= 20;

        return [
            'passed' => $passed,
            'issue' => $passed ? null : 'Output lacks a meaningful summary (min 20 chars)',
        ];
    }

    private function checkConfidenceDeclared(array $output): array
    {
        $passed = isset($output['confidence']) && is_numeric($output['confidence']);

        return [
            'passed' => $passed,
            'issue' => $passed ? null : 'No confidence score declared in output',
        ];
    }

    private function checkNoContradiction(array $output): array
    {
        $content = $this->stringify($output);
        $count = $this->countKeywords($content, [
            'however', 'but on the other hand', 'contradicts', 'contrary to',
            'on the contrary', 'conversely', 'inconsistent with',
        ]);

        $passed = $count < 4;

        return [
            'passed' => $passed,
            'issue' => $passed ? null : "High contradiction marker count ({$count}) — output may be internally inconsistent",
        ];
    }

    private function checkEvidencePresent(array $output): array
    {
        $evidence = $output['evidence'] ?? [];
        $passed = is_array($evidence) && count($evidence) > 0;

        return [
            'passed' => $passed,
            'issue' => $passed ? null : 'No evidence sources cited — claims are unsubstantiated',
        ];
    }

    private function checkAssumptionsDeclared(array $output): array
    {
        $passed = array_key_exists('assumptions', $output);

        return [
            'passed' => $passed,
            'issue' => $passed ? null : 'Assumptions not declared — hidden assumptions increase delusion risk',
        ];
    }

    private function checkRisksAcknowledged(array $output): array
    {
        $passed = array_key_exists('risks', $output);

        return [
            'passed' => $passed,
            'issue' => $passed ? null : 'No risk acknowledgement in output',
        ];
    }
}
