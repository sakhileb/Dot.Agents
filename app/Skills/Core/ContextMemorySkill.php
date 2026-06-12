<?php

namespace App\Skills\Core;

use App\Models\AgentDeployment;
use App\Models\AgentMemory;
use App\Skills\DTOs\SkillResult;

/**
 * ContextMemorySkill — context prioritization and memory injection (Layer 1 — Core)
 *
 * Handles the two memory-focused context actions extracted from ContextEngineeringSkill:
 *   prioritize – reorder context items by recency × relevance × importance
 *   inject     – merge episodic memory entries into the active context
 *
 * Shares scoring and token estimation helpers with ContextOptimizationSkill via ContextHelper.
 */
class ContextMemorySkill extends ContextHelper
{
    public function key(): string
    {
        return 'context-memory';
    }

    public function layer(): string
    {
        return 'core';
    }

    public function execute(array $input, array $context = []): SkillResult
    {
        $action = $input['action'] ?? 'prioritize';
        $deployment = $context['deployment'] ?? null;

        return match ($action) {
            'prioritize' => $this->prioritizeContext($input, $deployment),
            'inject' => $this->injectMemory($input, $deployment),
            default => SkillResult::failed("Unknown context-memory action: [{$action}]"),
        };
    }

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
}
