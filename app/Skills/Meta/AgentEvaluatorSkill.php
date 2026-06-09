<?php

namespace App\Skills\Meta;

use App\Models\AgentDeployment;
use App\Models\AgentScorecard;
use App\Models\AgentTask;
use App\Skills\BaseSkill;
use App\Skills\DTOs\SkillResult;

/**
 * Agent Evaluator Skill (Meta-Agent Layer)
 *
 * Scores the quality of any agent's completed task output across five dimensions:
 *   – Completeness: does the output cover all required fields?
 *   – Accuracy:     is the evidence base credible and verifiable?
 *   – Clarity:      is the output legible and well-structured?
 *   – Actionability: are the recommendations concrete and actionable?
 *   – Risk-awareness: are risks and assumptions properly declared?
 *
 * The overall score is written back to the AgentScorecard if a deployment is provided.
 */
class AgentEvaluatorSkill extends BaseSkill
{
    public function key(): string
    {
        return 'agent-evaluator';
    }

    public function layer(): string
    {
        return 'meta';
    }

    /**
     * Input keys:
     *   output       – the task output array to evaluate
     *   task         – the original AgentTask or its array representation
     *   deployment   – passed via $context['deployment']
     */
    public function execute(array $input, array $context = []): SkillResult
    {
        $output = $input['output'] ?? [];
        $task = $input['task'] ?? [];
        $deployment = $context['deployment'] ?? null;

        $dimensions = [
            'completeness' => $this->scoreCompleteness($output),
            'accuracy' => $this->scoreAccuracy($output),
            'clarity' => $this->scoreClarity($output),
            'actionability' => $this->scoreActionability($output),
            'risk_awareness' => $this->scoreRiskAwareness($output),
        ];

        $overallScore = round(
            array_sum(array_column($dimensions, 'score')) / count($dimensions),
            1
        );

        $findings = array_values(array_filter(
            array_map(fn ($d) => $d['feedback'] ?? null, $dimensions)
        ));

        if ($deployment instanceof AgentDeployment) {
            $this->persistToScorecard($deployment, $dimensions, $overallScore);
        }

        return SkillResult::completed(
            [
                'overall_score' => $overallScore,
                'grade' => $this->grade($overallScore),
                'dimensions' => $dimensions,
                'evaluation_time' => now()->toIso8601String(),
            ],
            100.0,
            $findings,
            $overallScore < 70 ? ['Review low-scoring dimensions and re-run task for quality improvement'] : []
        );
    }

    // ── Dimensions ───────────────────────────────────────

    private function scoreCompleteness(array $output): array
    {
        $score = 40.0;
        $feedback = null;

        if (isset($output['summary']) && strlen($output['summary']) >= 50) {
            $score += 20;
        }
        if (isset($output['result']) && ! empty($output['result'])) {
            $score += 20;
        }
        if (isset($output['recommendations']) && count($output['recommendations']) > 0) {
            $score += 20;
        }

        if ($score < 70) {
            $feedback = 'Output is incomplete — missing summary, results, or recommendations';
        }

        return ['score' => $this->clamp($score), 'feedback' => $feedback];
    }

    private function scoreAccuracy(array $output): array
    {
        $evidence = $output['evidence'] ?? [];
        $evidenceCount = is_array($evidence) ? count($evidence) : 0;
        $score = $this->clamp(50 + ($evidenceCount * 10));
        $feedback = $evidenceCount === 0 ? 'No evidence sources provided — accuracy cannot be verified' : null;

        return ['score' => $score, 'feedback' => $feedback];
    }

    private function scoreClarity(array $output): array
    {
        $summary = $output['summary'] ?? '';
        $score = 50.0;
        $feedback = null;

        if (is_string($summary) && strlen($summary) >= 100) {
            $score += 25;
        }
        if (isset($output['reasoning']) && strlen($output['reasoning'] ?? '') >= 50) {
            $score += 25;
        }

        if ($score < 70) {
            $feedback = 'Output lacks sufficient explanation — add detailed reasoning and summary';
        }

        return ['score' => $this->clamp($score), 'feedback' => $feedback];
    }

    private function scoreActionability(array $output): array
    {
        $recs = $output['recommendations'] ?? [];
        $count = is_array($recs) ? count($recs) : 0;
        $score = $this->clamp(50 + ($count * 15));

        return [
            'score' => $score,
            'feedback' => $count === 0 ? 'No actionable recommendations provided' : null,
        ];
    }

    private function scoreRiskAwareness(array $output): array
    {
        $hasRisks = isset($output['risks']) && is_array($output['risks']);
        $hasAssumptions = isset($output['assumptions']);
        $score = 40.0 + ($hasRisks ? 30 : 0) + ($hasAssumptions ? 30 : 0);

        return [
            'score' => $this->clamp($score),
            'feedback' => ! $hasRisks ? 'Risks not identified in output' : null,
        ];
    }

    // ── Scorecard persistence ────────────────────────────

    private function persistToScorecard(AgentDeployment $deployment, array $dimensions, float $score): void
    {
        AgentScorecard::updateOrCreate(
            [
                'agent_deployment_id' => $deployment->id,
                'period_type' => 'daily',
                'period_start' => now()->startOfDay(),
            ],
            [
                'organization_id' => $deployment->organization_id,
                'period_end' => now()->endOfDay(),
                'output_quality_score' => $score,
                'accuracy_score' => $dimensions['accuracy']['score'],
                'completeness_score' => $dimensions['completeness']['score'],
                'overall_score' => $score,
            ]
        );
    }
}
