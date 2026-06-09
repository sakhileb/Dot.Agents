<?php

namespace App\Skills\Workforce;

use App\Skills\BaseSkill;
use App\Skills\DTOs\SkillResult;

/**
 * Task Decomposition Skill (Layer 3 — Workforce)
 *
 * Breaks a complex task into a structured list of subtasks, each tagged with
 * a required skill key so the DelegationSkill can route them to the right agents.
 *
 * Strategy:
 *   – Detect task type from keyword patterns (analysis, content, research, risk…)
 *   – Build parallel subtasks for independent workstreams
 *   – Fall back to a generic 3-phase decomposition for complex opaque tasks
 */
class TaskDecompositionSkill extends BaseSkill
{
    public function key(): string
    {
        return 'task-decomposition';
    }

    public function layer(): string
    {
        return 'workforce';
    }

    /**
     * Input keys:
     *   task             – string description of the work
     *   complexity_score – 0-100 hint from orchestrator (default 50)
     */
    public function execute(array $input, array $context = []): SkillResult
    {
        $task = $input['task'] ?? $input['task_description'] ?? '';
        $complexityScore = (float) ($input['complexity_score'] ?? $this->estimateComplexity($task));

        if (empty(trim($task))) {
            return SkillResult::skipped('No task description provided for decomposition');
        }

        $subtasks = $this->decompose($task, $complexityScore);

        $strategy = match (true) {
            count($subtasks) > 3 => 'parallel',
            count($subtasks) > 1 => 'sequential',
            default => 'direct',
        };

        return SkillResult::completed(
            [
                'subtasks' => $subtasks,
                'subtask_count' => count($subtasks),
                'decomposition_strategy' => $strategy,
                'complexity_score' => $complexityScore,
            ],
            88.0,
            count($subtasks) === 0 ? ['No subtasks derived — task may be atomic or description is too vague'] : []
        );
    }

    // ── Decomposition logic ──────────────────────────────

    private function decompose(string $task, float $complexity): array
    {
        $taskLower = strtolower($task);
        $subtasks = [];

        // Analysis workstream
        if ($this->countKeywords($taskLower, ['analyz', 'assess', 'evaluat', 'review', 'audit']) > 0) {
            $subtasks[] = $this->subtask('data-analysis', 'Analyse data and extract key insights', 'analysis', $task);
        }

        // Research workstream
        if ($this->countKeywords($taskLower, ['research', 'investigat', 'discover', 'benchmark', 'compar']) > 0) {
            $subtasks[] = $this->subtask('market-research', 'Research relevant background information', 'research', $task);
        }

        // Content creation workstream
        if ($this->countKeywords($taskLower, ['report', 'write', 'document', 'draft', 'summar', 'present']) > 0) {
            $subtasks[] = $this->subtask('report-writing', 'Prepare written output and documentation', 'content', $task);
        }

        // Risk / compliance workstream
        if ($this->countKeywords($taskLower, ['risk', 'compliance', 'legal', 'regulat', 'policy', 'govern']) > 0) {
            $subtasks[] = $this->subtask('risk-analysis', 'Perform risk and compliance assessment', 'governance', $task);
        }

        // Financial workstream
        if ($this->countKeywords($taskLower, ['cost', 'budget', 'financ', 'roi', 'revenue', 'saving']) > 0) {
            $subtasks[] = $this->subtask('financial-analysis', 'Model financial impact and cost-benefit', 'analysis', $task);
        }

        // Forecasting workstream
        if ($this->countKeywords($taskLower, ['forecast', 'predict', 'project', 'trend', 'future']) > 0) {
            $subtasks[] = $this->subtask('forecasting', 'Build data-driven forecast', 'analysis', $task);
        }

        // Performance workstream
        if ($this->countKeywords($taskLower, ['kpi', 'performance', 'metric', 'sla', 'goal', 'track']) > 0) {
            $subtasks[] = $this->subtask('kpi-monitoring', 'Monitor and evaluate performance metrics', 'enterprise', $task);
        }

        // Generic three-phase decomposition for complex opaque tasks
        if (empty($subtasks) && $complexity > 50) {
            $subtasks = [
                $this->subtask('market-research', "Research background information for: {$task}", 'research', $task),
                $this->subtask('data-analysis', "Analyse findings and extract insights for: {$task}", 'analysis', $task),
                $this->subtask('report-writing', "Synthesise and document results for: {$task}", 'content', $task),
            ];
        }

        return $subtasks;
    }

    private function subtask(string $skillKey, string $description, string $type, string $parentTask): array
    {
        return [
            'key' => $skillKey.'-'.uniqid(),
            'required_skill' => $skillKey,
            'description' => $description,
            'type' => $type,
            'parent_task' => $parentTask,
            'priority' => 'medium',
        ];
    }

    private function estimateComplexity(string $task): float
    {
        $complexityMarkers = [
            'analyz', 'compar', 'evaluat', 'synthesiz', 'design', 'architect',
            'multiple', 'cross-department', 'strategic', 'comprehensive', 'full',
        ];

        $markerCount = $this->countKeywords($task, $complexityMarkers);
        $wordCount = str_word_count($task);

        return $this->clamp(
            ($markerCount * 15) + ($wordCount > 50 ? 20 : 0) + ($wordCount > 100 ? 20 : 0)
        );
    }
}
