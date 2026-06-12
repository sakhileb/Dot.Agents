<?php

namespace App\Skills\Core;

use App\Models\AgentDeployment;
use App\Models\AgentTask;
use App\Skills\DTOs\SkillResult;

/**
 * ContextOptimizationSkill — context window optimization and compression (Layer 1 — Core)
 *
 * Handles the two optimization-focused context actions extracted from ContextEngineeringSkill:
 *   optimize  – score context items and produce an optimised arrangement
 *   compress  – losslessly reduce context size while preserving key facts
 *
 * Shares token estimation and scoring helpers with ContextMemorySkill via ContextHelper.
 */
class ContextOptimizationSkill extends ContextHelper
{
    public function key(): string
    {
        return 'context-optimization';
    }

    public function layer(): string
    {
        return 'core';
    }

    public function execute(array $input, array $context = []): SkillResult
    {
        $action = $input['action'] ?? 'optimize';
        $deployment = $context['deployment'] ?? null;
        $task = $context['task'] ?? null;

        return match ($action) {
            'optimize' => $this->optimizeContext($input, $deployment, $task),
            'compress' => $this->compressContext($input),
            default => SkillResult::failed("Unknown context-optimization action: [{$action}]"),
        };
    }

    private function optimizeContext(array $input, ?AgentDeployment $deployment, mixed $task): SkillResult
    {
        $items = $input['context_items'] ?? [];
        $tokenBudget = (int) ($input['token_budget'] ?? self::DEFAULT_TOKEN_BUDGET);
        $goal = $input['goal'] ?? ($task instanceof AgentTask ? $task->description : 'complete the assigned task');

        if (empty($items) && $deployment) {
            $items = $this->buildItemsFromMemory($deployment);
        }

        $scored = $this->scoreItems($items, $goal);
        $optimised = $this->fitToTokenBudget($scored, $tokenBudget);

        $totalTokens = array_sum(array_column($optimised, 'estimated_tokens'));
        $coverageScore = $this->clamp(min(100, ($totalTokens / max(1, $tokenBudget)) * 120));

        $findings = [];
        $recommendations = [];

        if (count($optimised) < count($scored)) {
            $dropped = count($scored) - count($optimised);
            $findings[] = "{$dropped} context item(s) dropped to stay within {$tokenBudget} token budget";
            $recommendations[] = 'Increase token_budget or compress individual context items to include more history';
        }

        if ($coverageScore < 50) {
            $recommendations[] = 'Context is sparse — consider injecting episodic memory with action: inject';
        }

        return SkillResult::completed(
            [
                'optimised_items' => $optimised,
                'total_items' => count($optimised),
                'estimated_tokens' => $totalTokens,
                'token_budget' => $tokenBudget,
                'coverage_score' => round($coverageScore, 1),
                'goal_alignment' => $this->computeGoalAlignment($optimised, $goal),
            ],
            90.0,
            $findings,
            $recommendations
        );
    }

    private function compressContext(array $input): SkillResult
    {
        $items = $input['context_items'] ?? [];
        $targetTokens = (int) ($input['token_budget'] ?? self::DEFAULT_TOKEN_BUDGET / 2);

        if (empty($items)) {
            return SkillResult::skipped('No context items provided for compression');
        }

        $compressed = [];
        $runningTokens = 0;
        $compressedCount = 0;

        foreach ($items as $item) {
            $content = $item['content'] ?? '';
            $tokens = $item['tokens'] ?? $this->estimateTokens($content);
            $importance = $item['importance'] ?? 50.0;

            if ($runningTokens + $tokens <= $targetTokens) {
                $compressed[] = array_merge($item, ['estimated_tokens' => $tokens, 'compressed' => false]);
                $runningTokens += $tokens;
            } elseif ($importance >= self::LOW_IMPORTANCE_THRESHOLD) {
                $stub = substr($content, 0, 100).'…[compressed]';
                $stubTokens = $this->estimateTokens($stub);
                $compressed[] = array_merge($item, ['content' => $stub, 'estimated_tokens' => $stubTokens, 'compressed' => true]);
                $runningTokens += $stubTokens;
                $compressedCount++;
            }
        }

        $originalTokens = array_sum(array_map(fn ($i) => $i['tokens'] ?? $this->estimateTokens($i['content'] ?? ''), $items));
        $compressionRatio = $originalTokens > 0 ? round(1 - ($runningTokens / max(1, $originalTokens)), 3) : 0.0;

        return SkillResult::completed(
            [
                'compressed_items' => $compressed,
                'original_count' => count($items),
                'final_count' => count($compressed),
                'items_compressed' => $compressedCount,
                'estimated_tokens' => $runningTokens,
                'target_tokens' => $targetTokens,
                'compression_ratio' => $compressionRatio,
            ],
            92.0
        );
    }
}
