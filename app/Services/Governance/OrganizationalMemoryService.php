<?php

namespace App\Services\Governance;

use App\Models\AgentMemory;
use App\Models\Organization;
use App\Services\Governance\Memory\MemoryScoreCalculator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * OrganizationalMemoryService
 *
 * Manages the organization's collective intelligence (memories, lessons, knowledge).
 * Scoring logic is delegated to MemoryScoreCalculator.
 *
 * @see MemoryScoreCalculator
 */
class OrganizationalMemoryService
{
    private const CACHE_TTL = 3600;

    public function __construct(
        private readonly MemoryScoreCalculator $calculator,
    ) {}

    public function calculate(Organization $organization): array
    {
        $cacheKey = "org_memory_score:{$organization->id}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($organization) {
            return $this->calculator->compute($organization->id);
        });
    }

    public function knowledgeGraphSummary(Organization $organization): array
    {
        $cacheKey = "org_kg_summary:{$organization->id}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($organization) {
            return $this->calculator->buildKnowledgeGraphSummary($organization->id);
        });
    }

    public function invalidate(Organization $organization): void
    {
        Cache::forget("org_memory_score:{$organization->id}");
        Cache::forget("org_kg_summary:{$organization->id}");
    }

    /**
     * Record a lesson learned from a failure or decision outcome.
     */
    public function recordLesson(
        int $deploymentId,
        int $organizationId,
        string $subject,
        string $content,
        array $context = []
    ): AgentMemory {
        $memory = AgentMemory::withoutGlobalScope('organization')->create([
            'uuid' => Str::uuid()->toString(),
            'agent_deployment_id' => $deploymentId,
            'organization_id' => $organizationId,
            'memory_type' => 'long_term',
            'memory_category' => 'lesson',
            'subject' => $subject,
            'content' => $content,
            'context' => $context,
            'importance_score' => 80,
            'is_verified' => false,
            'is_active' => true,
        ]);

        Cache::forget("org_memory_score:{$organizationId}");
        Cache::forget("org_kg_summary:{$organizationId}");

        Log::info('[OrganizationalMemory] Lesson recorded', [
            'memory_id' => $memory->id,
            'deployment_id' => $deploymentId,
            'organization_id' => $organizationId,
            'subject' => $subject,
        ]);

        return $memory;
    }

    /**
     * Mark a memory as human-validated knowledge.
     */
    public function verifyMemory(AgentMemory $memory): void
    {
        $memory->update(['is_verified' => true]);
        Cache::forget("org_memory_score:{$memory->organization_id}");

        Log::info('[OrganizationalMemory] Memory verified', ['memory_id' => $memory->id]);
    }
}
