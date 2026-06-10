<?php

namespace App\Services\Governance\DataTrust;

use App\Models\AgentMemory;
use App\Models\AgentTask;
use App\Models\DecisionLog;
use App\Models\KnowledgeArticle;

/**
 * DataQualityScorer
 *
 * Computes the three data-centric dimensions of the Data Trust Score:
 *  - Data Completeness   (25 pts)
 *  - Data Integrity      (25 pts)
 *  - Data Freshness      (20 pts)
 *
 * This class is purely computational — no caching, no side-effects.
 * Extracted from DataTrustScoreService.
 */
class DataQualityScorer
{
    /**
     * Score data completeness: completed tasks with all required fields.
     */
    public function scoreCompleteness(int $orgId): array
    {
        $taskTotal = AgentTask::withoutGlobalScope('organization')
            ->where('organization_id', $orgId)->count();

        $incompleteTaskCount = AgentTask::withoutGlobalScope('organization')
            ->where('organization_id', $orgId)
            ->where('status', 'completed')
            ->where(function ($q) {
                $q->whereNull('confidence_score')->orWhereNull('result_summary');
            })
            ->count();

        $completedTasks = AgentTask::withoutGlobalScope('organization')
            ->where('organization_id', $orgId)
            ->where('status', 'completed')->count();

        $ratio = $completedTasks > 0 ? 1 - ($incompleteTaskCount / $completedTasks) : 1;

        return [
            'score' => round($ratio * 25, 2),
            'max' => 25,
            'total_tasks' => $taskTotal,
            'incomplete_tasks' => $incompleteTaskCount,
            'completeness_rate' => round($ratio * 100, 2),
        ];
    }

    /**
     * Score data integrity: orphan decisions and articles.
     */
    public function scoreIntegrity(int $orgId): array
    {
        $orphanDecisions = DecisionLog::withoutGlobalScope('organization')
            ->where('organization_id', $orgId)
            ->whereNotNull('task_id')
            ->whereNotIn('task_id', AgentTask::withoutGlobalScope('organization')
                ->where('organization_id', $orgId)
                ->withTrashed()
                ->pluck('id'))
            ->count();

        $orphanArticles = KnowledgeArticle::withoutGlobalScope('organization')
            ->where('organization_id', $orgId)
            ->whereNull('knowledge_base_id')
            ->count();

        $totalDecisions = DecisionLog::withoutGlobalScope('organization')
            ->where('organization_id', $orgId)->count();

        $totalArticles = KnowledgeArticle::withoutGlobalScope('organization')
            ->where('organization_id', $orgId)->count();

        $orphanRate = ($totalDecisions + $totalArticles) > 0
            ? ($orphanDecisions + $orphanArticles) / ($totalDecisions + $totalArticles)
            : 0;

        return [
            'score' => round(max(0, (1 - $orphanRate) * 25), 2),
            'max' => 25,
            'orphan_decisions' => $orphanDecisions,
            'orphan_articles' => $orphanArticles,
            'orphan_rate' => round($orphanRate * 100, 2),
        ];
    }

    /**
     * Score data freshness: stale knowledge articles and agent memories.
     */
    public function scoreFreshness(int $orgId): array
    {
        $staleThreshold = now()->subDays(90);
        $staleMemoryThreshold = now()->subDays(30);

        $totalArticles = KnowledgeArticle::withoutGlobalScope('organization')
            ->where('organization_id', $orgId)->count();

        $staleArticles = KnowledgeArticle::withoutGlobalScope('organization')
            ->where('organization_id', $orgId)
            ->where('updated_at', '<', $staleThreshold)->count();

        $staleMemories = AgentMemory::withoutGlobalScope('organization')
            ->where('organization_id', $orgId)
            ->where('is_active', true)
            ->where('memory_type', 'long_term')
            ->where(function ($q) use ($staleMemoryThreshold) {
                $q->whereNull('last_accessed_at')
                    ->orWhere('last_accessed_at', '<', $staleMemoryThreshold);
            })->count();

        $totalMemories = AgentMemory::withoutGlobalScope('organization')
            ->where('organization_id', $orgId)
            ->where('is_active', true)
            ->where('memory_type', 'long_term')->count();

        $articleFreshRate = $totalArticles > 0 ? 1 - ($staleArticles / $totalArticles) : 1;
        $memoryFreshRate = $totalMemories > 0 ? 1 - ($staleMemories / $totalMemories) : 1;
        $combined = ($articleFreshRate + $memoryFreshRate) / 2;

        return [
            'score' => round($combined * 20, 2),
            'max' => 20,
            'stale_articles' => $staleArticles,
            'total_articles' => $totalArticles,
            'stale_memories' => $staleMemories,
            'total_memories' => $totalMemories,
            'freshness_rate' => round($combined * 100, 2),
        ];
    }
}
