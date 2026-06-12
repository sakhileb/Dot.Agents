<?php

namespace App\Skills\Core;

use App\Models\AgentDeployment;
use App\Models\AgentMemory;
use App\Skills\BaseSkill;

/**
 * ContextHelper — shared context scoring and fitting utilities.
 *
 * Extracted from ContextEngineeringSkill to eliminate duplication between
 * ContextOptimizationSkill and ContextMemorySkill. Contains pure stateless
 * helper methods for token estimation, item scoring, budget fitting, and
 * goal alignment calculation.
 */
abstract class ContextHelper extends BaseSkill
{
    /** Soft token ceiling used when compressing context. */
    protected const DEFAULT_TOKEN_BUDGET = 4096;

    /** Importance below which memory entries are considered low-signal. */
    protected const LOW_IMPORTANCE_THRESHOLD = 30.0;

    /**
     * Build context items from an agent deployment's recent memories.
     *
     * @return array<int, array>
     */
    protected function buildItemsFromMemory(AgentDeployment $deployment): array
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

    /**
     * Score context items for relevance and priority against a goal string.
     *
     * @param  array<int, array>  $items
     * @return array<int, array>
     */
    protected function scoreItems(array $items, string $goal): array
    {
        $goalWords = array_filter(explode(' ', strtolower($goal)));

        return array_map(function (array $item) use ($goalWords) {
            $content = strtolower($item['content'] ?? '');
            $importance = (float) ($item['importance'] ?? 50.0);
            $tokens = $item['tokens'] ?? $this->estimateTokens($item['content'] ?? '');

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

    /**
     * Return the highest-scoring items that fit within a token budget.
     *
     * @param  array<int, array>  $scoredItems
     * @return array<int, array>
     */
    protected function fitToTokenBudget(array $scoredItems, int $budget): array
    {
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

    /** Compute average relevance score across optimised context items. */
    protected function computeGoalAlignment(array $items, string $goal): float
    {
        if (empty($items) || empty($goal)) {
            return 0.0;
        }

        $avg = array_sum(array_column($items, 'relevance_score')) / count($items);

        return round($this->clamp($avg), 1);
    }

    /** Rough approximation: ~4 characters per LLM token. */
    protected function estimateTokens(string $text): int
    {
        return (int) ceil(strlen($text) / 4);
    }
}
