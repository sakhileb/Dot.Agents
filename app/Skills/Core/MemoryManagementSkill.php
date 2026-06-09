<?php

namespace App\Skills\Core;

use App\Models\AgentDeployment;
use App\Models\AgentMemory;
use App\Skills\BaseSkill;
use App\Skills\DTOs\SkillResult;

/**
 * Memory Management Skill (Layer 1 — Core Worker)
 *
 * Manages the agent's memory store: statistics, pruning of stale low-value
 * memories, re-prioritisation, and summarisation of memory load.
 *
 * Actions:
 *   stats      – return memory statistics grouped by type
 *   prune      – delete memories below importance threshold and/or past max age
 *   prioritize – boost importance of recently-accessed memories
 *   summarize  – return top-N most important memories
 */
class MemoryManagementSkill extends BaseSkill
{
    public function key(): string
    {
        return 'memory-management';
    }

    public function layer(): string
    {
        return 'core';
    }

    public function execute(array $input, array $context = []): SkillResult
    {
        $action = $input['action'] ?? 'stats';
        $deployment = $context['deployment'] ?? null;

        if (! $deployment instanceof AgentDeployment) {
            return SkillResult::failed('No deployment context — memory management requires an active agent deployment');
        }

        return match ($action) {
            'stats' => $this->memoryStats($deployment),
            'prune' => $this->pruneMemory($deployment, $input),
            'prioritize' => $this->reprioritise($deployment),
            'summarize' => $this->summariseMemory($deployment, $input),
            default => SkillResult::failed("Unknown memory action: [{$action}]"),
        };
    }

    // ── Actions ──────────────────────────────────────────

    private function memoryStats(AgentDeployment $deployment): SkillResult
    {
        $stats = AgentMemory::where('agent_deployment_id', $deployment->id)
            ->selectRaw('memory_type, count(*) as count, avg(importance_score) as avg_importance, max(updated_at) as last_updated')
            ->groupBy('memory_type')
            ->get()
            ->keyBy('memory_type');

        $totalCount = $stats->sum('count');

        return SkillResult::completed(
            [
                'total_memories' => $totalCount,
                'by_type' => $stats->toArray(),
                'last_activity' => AgentMemory::where('agent_deployment_id', $deployment->id)->max('updated_at'),
            ],
            100.0
        );
    }

    private function pruneMemory(AgentDeployment $deployment, array $input): SkillResult
    {
        $maxAgeDays = (int) ($input['max_age_days'] ?? 30);
        $minImportance = (float) ($input['min_importance'] ?? 20.0);

        $deleted = AgentMemory::where('agent_deployment_id', $deployment->id)
            ->where('importance_score', '<', $minImportance)
            ->where('updated_at', '<', now()->subDays($maxAgeDays))
            ->delete();

        $findings = $deleted > 0
            ? ["{$deleted} stale low-value memories pruned"]
            : [];

        return SkillResult::completed(
            [
                'pruned_count' => $deleted,
                'criteria' => ['max_age_days' => $maxAgeDays, 'min_importance' => $minImportance],
            ],
            100.0,
            $findings
        );
    }

    private function reprioritise(AgentDeployment $deployment): SkillResult
    {
        // Boost importance of recently accessed memories by +10 (capped at 100)
        $boosted = AgentMemory::where('agent_deployment_id', $deployment->id)
            ->where('updated_at', '>=', now()->subDays(7))
            ->where('importance_score', '<', 90)
            ->increment('importance_score', 10);

        return SkillResult::completed(
            ['boosted_count' => $boosted],
            100.0
        );
    }

    private function summariseMemory(AgentDeployment $deployment, array $input): SkillResult
    {
        $limit = (int) ($input['limit'] ?? 10);
        $type = $input['memory_type'] ?? null;

        $query = AgentMemory::where('agent_deployment_id', $deployment->id)
            ->orderByDesc('importance_score');

        if ($type) {
            $query->where('memory_type', $type);
        }

        $memories = $query->take($limit)->get(['subject', 'content', 'memory_type', 'importance_score', 'updated_at']);

        return SkillResult::completed(
            [
                'memories' => $memories->toArray(),
                'count' => $memories->count(),
                'filtered_by_type' => $type,
            ],
            100.0
        );
    }
}
