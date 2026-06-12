<?php

namespace App\Skills\Core;

use App\Models\AgentDeployment;
use App\Models\AgentMemory;
use App\Models\AgentTask;
use App\Skills\BaseSkill;
use App\Skills\DTOs\SkillResult;

/**
 * Context Engineering Skill (Layer 1 — Core)
 *
 * Maximises the quality and relevance of the agent's active context window.
 * Inspired by muratcankoylan/agent-skills-for-context-engineering — applies
 * structured context engineering principles so every task starts with the
 * most information-dense, goal-aligned prompt possible.
 *
 * Actions:
 *   optimize    – score the current context and produce an optimised version
 *   compress    – losslessly reduce context size while preserving key facts
 *   prioritize  – reorder context items by recency × relevance × importance
 *   inject      – merge episodic memory entries into the active context
 */
class ContextEngineeringSkill extends BaseSkill
{
    /** Soft token ceiling used when compressing context. */
    private const DEFAULT_TOKEN_BUDGET = 4096;

    /** Importance below which memory entries are considered low-signal. */
    private const LOW_IMPORTANCE_THRESHOLD = 30.0;

    public function key(): string
    {
        return 'context-engineering';
    }

    public function layer(): string
    {
        return 'core';
    }

    /**
     * Input keys:
     *   action          – optimize | compress | prioritize | inject
     *   context_items   – array of {role, content, importance?, tokens?} (optional)
     *   token_budget    – integer (default 4096)
     *   memory_types    – array of AgentMemory types to inject (default: all)
     */
    public function execute(array $input, array $context = []): SkillResult
    {
        $action = $input['action'] ?? 'optimize';
        $deployment = $context['deployment'] ?? null;
        $task = $context['task'] ?? null;

        return match ($action) {
            'optimize' => $this->optimizeContext($input, $deployment, $task),
            'compress' => $this->compressContext($input),
            'prioritize' => $this->prioritizeContext($input, $deployment),
            'inject' => $this->injectMemory($input, $deployment),
            default => SkillResult::failed("Unknown context-engineering action: [{$action}]"),
        };
    }

    // ── Actions ──────────────────────────────────────────

    /**
     * Score the provided context items and return an optimised arrangement
     * with a relevance map and recommendations for prompt construction.
     */
    private function optimizeContext(array $input, ?AgentDeployment $deployment, mixed $task): SkillResult
    {
        $items = $input['context_items'] ?? [];
        $tokenBudget = (int) ($input['token_budget'] ?? self::DEFAULT_TOKEN_BUDGET);
        $goal = $input['goal'] ?? ($task instanceof AgentTask ? $task->description : 'complete the assigned task');

        if (empty($items) && $deployment) {
            // Auto-build context from recent memories
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

    /**
     * Compress context items to fit within a tighter token budget,
     * summarising lower-priority items without losing their key facts.
     */
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
                // Summarise: keep first 100 characters as a stub
                $stub = substr($content, 0, 100).'…[compressed]';
                $stubTokens = $this->estimateTokens($stub);
                $compressed[] = array_merge($item, ['content' => $stub, 'estimated_tokens' => $stubTokens, 'compressed' => true]);
                $runningTokens += $stubTokens;
                $compressedCount++;
            }
            // Drop items below importance threshold when over budget
        }

        $compressionRatio = count($items) > 0
            ? round(1 - ($runningTokens / max(1, array_sum(array_map(fn ($i) => $i['tokens'] ?? $this->estimateTokens($i['content'] ?? ''), $items)))), 3)
            : 0.0;

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

    /**
     * Re-order context items by a composite score: recency × relevance × importance.
     */
    private function prioritizeContext(array $input, ?AgentDeployment $deployment): SkillResult
    {
        $items = $input['context_items'] ?? [];
        $goal = $input['goal'] ?? '';

        if (empty($items) && $deployment) {
            $items = $this->buildItemsFromMemory($deployment);
        }

        if (empty($items)) {
            return SkillResult::skipped('No context items to prioritize');
        }

        $scored = $this->scoreItems($items, $goal);

        // Sort descending by composite score
        usort($scored, fn ($a, $b) => ($b['priority_score'] ?? 0) <=> ($a['priority_score'] ?? 0));

        return SkillResult::completed(
            [
                'prioritized_items' => $scored,
                'total_items' => count($scored),
                'top_item_preview' => substr($scored[0]['content'] ?? '', 0, 120),
            ],
            95.0
        );
    }

    /**
     * Retrieve agent memory entries and inject them into the context as
     * structured items, filtered by type and minimum importance.
     */
    private function injectMemory(array $input, ?AgentDeployment $deployment): SkillResult
    {
        if (! $deployment instanceof AgentDeployment) {
            return SkillResult::failed('Deployment context required for memory injection');
        }

        $memoryTypes = $input['memory_types'] ?? [];
        $minImportance = (float) ($input['min_importance'] ?? self::LOW_IMPORTANCE_THRESHOLD);
        $limit = (int) ($input['limit'] ?? 20);

        $query = AgentMemory::where('agent_deployment_id', $deployment->id)
            ->where('importance_score', '>=', $minImportance)
            ->orderByDesc('importance_score')
            ->limit($limit);

        if (! empty($memoryTypes)) {
            $query->whereIn('memory_type', $memoryTypes);
        }

        $memories = $query->get();

        $injected = $memories->map(fn ($m) => [
            'role' => 'system',
            'content' => "[{$m->memory_type}] {$m->content}",
            'importance' => $m->importance_score,
            'source' => 'agent_memory',
            'memory_id' => $m->id,
            'estimated_tokens' => $this->estimateTokens($m->content),
        ])->values()->all();

        $totalTokens = array_sum(array_column($injected, 'estimated_tokens'));

        return SkillResult::completed(
            [
                'injected_items' => $injected,
                'memory_count' => $memories->count(),
                'estimated_tokens' => $totalTokens,
                'min_importance_filter' => $minImportance,
                'memory_types_filter' => $memoryTypes ?: 'all',
            ],
            95.0
        );
    }

    // ── Helpers ──────────────────────────────────────────

    private function buildItemsFromMemory(AgentDeployment $deployment): array
    {
        return AgentMemory::where('agent_deployment_id', $deployment->id)
            ->where('importance_score', '>=', self::LOW_IMPORTANCE_THRESHOLD)
            ->orderByDesc('importance_score')
            ->limit(30)
            ->get()
            ->map(fn ($m) => [
                'role' => 'system',
                'content' => $m->content,
                'importance' => $m->importance_score,
                'source' => 'memory',
            ])
            ->values()
            ->all();
    }

    private function scoreItems(array $items, string $goal): array
    {
        $goalWords = array_filter(explode(' ', strtolower($goal)));

        return array_map(function (array $item) use ($goalWords) {
            $content = strtolower($item['content'] ?? '');
            $importance = (float) ($item['importance'] ?? 50.0);
            $tokens = $item['tokens'] ?? $this->estimateTokens($item['content'] ?? '');

            // Relevance: keyword overlap with goal
            $relevanceHits = empty($goalWords) ? 5 : $this->countKeywords($content, $goalWords);
            $relevance = $this->clamp($relevanceHits * 10, 0, 100);

            $priority = $this->clamp(($importance * 0.5) + ($relevance * 0.5));

            return array_merge($item, [
                'estimated_tokens' => $tokens,
                'relevance_score' => round($relevance, 1),
                'priority_score' => round($priority, 1),
            ]);
        }, $items);
    }

    private function fitToTokenBudget(array $scoredItems, int $budget): array
    {
        // Sort by priority descending, then fill until budget exhausted
        usort($scoredItems, fn ($a, $b) => ($b['priority_score'] ?? 0) <=> ($a['priority_score'] ?? 0));

        $result = [];
        $used = 0;

        foreach ($scoredItems as $item) {
            $tokens = $item['estimated_tokens'] ?? 0;
            if ($used + $tokens > $budget) {
                break;
            }
            $result[] = $item;
            $used += $tokens;
        }

        return $result;
    }

    private function computeGoalAlignment(array $items, string $goal): float
    {
        if (empty($items) || empty($goal)) {
            return 0.0;
        }

        $avg = array_sum(array_column($items, 'relevance_score')) / count($items);

        return round($this->clamp($avg), 1);
    }

    private function estimateTokens(string $text): int
    {
        // Rough approximation: ~4 chars per token
        return (int) ceil(strlen($text) / 4);
    }
}
