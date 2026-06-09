<?php

namespace App\Skills\Workforce;

use App\Models\AgentDeployment;
use App\Models\AgentMemory;
use App\Skills\BaseSkill;
use App\Skills\DTOs\SkillResult;

/**
 * Collaboration Skill (Layer 3 — Workforce)
 *
 * Enables agents to share context, read shared context from collaborating agents,
 * and build joint recommendations by consolidating multiple agent outputs.
 *
 * Actions:
 *   share     – write context into org-scoped shared memory
 *   read      – read shared context entries from memory
 *   recommend – consolidate multiple recommendations into a joint set
 */
class CollaborationSkill extends BaseSkill
{
    public function key(): string
    {
        return 'collaboration';
    }

    public function layer(): string
    {
        return 'workforce';
    }

    /**
     * Input keys:
     *   action      – 'share' | 'read' | 'recommend'
     *   context_key – memory key for share/read
     *   content     – payload for 'share'
     *   recommendations – array of strings for 'recommend'
     */
    public function execute(array $input, array $context = []): SkillResult
    {
        $action = $input['action'] ?? 'share';
        $deployment = $context['deployment'] ?? null;

        return match ($action) {
            'share' => $this->shareContext($input, $deployment),
            'read' => $this->readSharedContext($input, $deployment),
            'recommend' => $this->buildJointRecommendation($input),
            default => SkillResult::failed("Unknown collaboration action: [{$action}]"),
        };
    }

    // ── Action handlers ──────────────────────────────────

    private function shareContext(array $input, mixed $deployment): SkillResult
    {
        $key = $input['context_key'] ?? 'collaboration_context';
        $content = $input['content'] ?? '';

        if (! $deployment instanceof AgentDeployment) {
            return SkillResult::failed('Cannot share context — no deployment in context');
        }

        AgentMemory::updateOrCreate(
            [
                'agent_deployment_id' => $deployment->id,
                'memory_type' => 'organizational',
                'subject' => $key,
            ],
            [
                'organization_id' => $deployment->organization_id,
                'content' => is_array($content) ? json_encode($content) : (string) $content,
                'importance_score' => 80,
                'metadata' => ['shared_at' => now()->toIso8601String(), 'skill' => $this->key()],
            ]
        );

        return SkillResult::completed(
            ['shared_key' => $key, 'status' => 'shared'],
            100.0
        );
    }

    private function readSharedContext(array $input, mixed $deployment): SkillResult
    {
        $key = $input['context_key'] ?? 'collaboration_context';

        if (! $deployment instanceof AgentDeployment) {
            return SkillResult::failed('Cannot read context — no deployment in context');
        }

        $memories = AgentMemory::where('organization_id', $deployment->organization_id)
            ->where('memory_type', 'organizational')
            ->where('subject', $key)
            ->orderByDesc('updated_at')
            ->get(['content', 'metadata', 'updated_at', 'agent_deployment_id']);

        return SkillResult::completed(
            [
                'context_key' => $key,
                'entries' => $memories->toArray(),
                'count' => $memories->count(),
            ],
            100.0
        );
    }

    private function buildJointRecommendation(array $input): SkillResult
    {
        $recommendations = $input['recommendations'] ?? [];

        if (empty($recommendations)) {
            return SkillResult::skipped('No recommendations provided to consolidate');
        }

        // Frequency-based consolidation: surface the most agreed-upon points
        $freq = [];
        foreach ($recommendations as $rec) {
            $normalised = strtolower(trim((string) $rec));
            $freq[$normalised] = ($freq[$normalised] ?? 0) + 1;
        }

        arsort($freq);

        $deduplicated = array_map(
            fn ($key) => array_values(array_filter($recommendations, fn ($r) => strtolower(trim($r)) === $key))[0],
            array_keys($freq)
        );

        $joint = array_slice($deduplicated, 0, 5);
        $consensus = count($deduplicated) < count($recommendations);

        return SkillResult::completed(
            [
                'joint_recommendations' => $joint,
                'source_count' => count($recommendations),
                'unique_count' => count($deduplicated),
                'consensus_reached' => $consensus,
            ],
            $consensus ? 92.0 : 78.0
        );
    }
}
