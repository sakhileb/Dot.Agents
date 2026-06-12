<?php

namespace App\Skills\Meta;

use App\Models\AgentDeployment;
use App\Models\AgentSkill;
use App\Models\AgentSkillAssignment;
use App\Services\AI\SkillRegistryService;
use App\Skills\BaseSkill;
use App\Skills\DTOs\SkillResult;

/**
 * Superpowers Skill (Layer 6 — Meta-Agent)
 *
 * A meta-capability framework that dynamically extends an agent's effective
 * skill set at runtime. Inspired by obra/superpowers — treats capabilities
 * as first-class composable units: agents can introspect their powers,
 * combine multiple skills into compound operations, and augment their
 * own capability profile without manual admin intervention.
 *
 * Actions:
 *   introspect  – list all skills available to this agent, with health scores
 *   extend      – dynamically activate an additional skill for this deployment
 *   combine     – execute multiple skills sequentially and merge their outputs
 *   augment     – enhance a skill's input with contextual intelligence before execution
 */
class SuperpowersSkill extends BaseSkill
{
    public function key(): string
    {
        return 'superpowers';
    }

    public function layer(): string
    {
        return 'meta';
    }

    /**
     * Input keys:
     *   action          – introspect | extend | combine | augment
     *   skill_keys      – array of skill keys (for combine / augment)
     *   skill_key       – single skill key (for extend)
     *   inputs          – map of skill_key → input array (for combine)
     *   base_input      – input array to augment (for augment)
     *   target_skill    – skill key whose input to augment (for augment)
     *   include_disabled – bool (for introspect, default false)
     */
    public function execute(array $input, array $context = []): SkillResult
    {
        $action = $input['action'] ?? 'introspect';
        $deployment = $context['deployment'] ?? null;

        return match ($action) {
            'introspect' => $this->introspect($input, $deployment),
            'extend' => $this->extend($input, $deployment),
            'combine' => $this->combine($input, $context),
            'augment' => $this->augment($input, $context),
            default => SkillResult::failed("Unknown superpowers action: [{$action}]"),
        };
    }

    // ── Actions ──────────────────────────────────────────

    /**
     * Return a full inventory of this agent's skills, their current status,
     * and confidence scores — giving the agent situational awareness of its
     * own capability boundaries.
     */
    private function introspect(?array $input, ?AgentDeployment $deployment): SkillResult
    {
        $includeDisabled = (bool) ($input['include_disabled'] ?? false);

        if (! $deployment instanceof AgentDeployment) {
            return SkillResult::failed('Deployment context required to introspect agent skills');
        }

        $query = AgentSkillAssignment::where('agent_deployment_id', $deployment->id)
            ->with('skill:id,key,name,layer,risk_level,confidence_score');

        if (! $includeDisabled) {
            $query->where('is_enabled', true);
        }

        $assignments = $query->get();

        $skills = $assignments->map(fn ($a) => [
            'key' => $a->skill?->key,
            'name' => $a->skill?->name,
            'layer' => $a->skill?->layer,
            'risk_level' => $a->skill?->risk_level,
            'confidence_score' => $a->skill?->confidence_score,
            'enabled' => $a->is_enabled,
            'has_implementation' => app(SkillRegistryService::class)->hasImplementation($a->skill?->key ?? ''),
        ])->sortBy('layer')->values()->all();

        $layers = array_unique(array_column($skills, 'layer'));
        $implementedCount = count(array_filter($skills, fn ($s) => $s['has_implementation']));

        return SkillResult::completed(
            [
                'deployment_id' => $deployment->id,
                'deployment_name' => $deployment->name,
                'skill_count' => count($skills),
                'implemented_count' => $implementedCount,
                'unimplemented_count' => count($skills) - $implementedCount,
                'layers_active' => array_values($layers),
                'skills' => $skills,
                'capability_coverage' => count($skills) > 0 ? round($implementedCount / count($skills) * 100, 1) : 0,
            ],
            100.0
        );
    }

    /**
     * Dynamically enable an additional skill on this deployment without
     * requiring a UI change — useful for autonomous agents that recognise
     * they need a new capability to complete a task.
     *
     * Only skills already registered in the catalogue can be activated.
     * High-risk skills still require human approval.
     */
    private function extend(?array $input, ?AgentDeployment $deployment): SkillResult
    {
        if (! $deployment instanceof AgentDeployment) {
            return SkillResult::failed('Deployment context required to extend agent capabilities');
        }

        $skillKey = $input['skill_key'] ?? '';

        if (empty($skillKey)) {
            return SkillResult::failed('skill_key is required for extend action');
        }

        $skill = AgentSkill::where('key', $skillKey)->where('is_active', true)->first();

        if (! $skill) {
            return SkillResult::failed("Skill '{$skillKey}' not found in the catalogue or is inactive");
        }

        // Block autonomous activation of critical-risk skills
        if (in_array($skill->risk_level, ['critical', 'high'], true)) {
            return SkillResult::failed(
                "Skill '{$skillKey}' has risk level '{$skill->risk_level}' — human approval required to activate"
            );
        }

        // Already assigned?
        $existing = AgentSkillAssignment::where('agent_deployment_id', $deployment->id)
            ->where('skill_id', $skill->id)
            ->first();

        if ($existing) {
            if ($existing->is_enabled) {
                return SkillResult::skipped("Skill '{$skillKey}' is already enabled on this deployment");
            }
            $existing->update(['is_enabled' => true]);
        } else {
            AgentSkillAssignment::create([
                'agent_deployment_id' => $deployment->id,
                'skill_id' => $skill->id,
                'organization_id' => $deployment->organization_id,
                'is_enabled' => true,
                'assigned_by' => 'superpowers_skill',
            ]);
        }

        return SkillResult::completed(
            [
                'activated_skill' => $skillKey,
                'skill_name' => $skill->name,
                'layer' => $skill->layer,
                'deployment_id' => $deployment->id,
                'status' => 'activated',
            ],
            92.0,
            [],
            ['Verify the newly activated skill with an introspect call before relying on it in tasks']
        );
    }

    /**
     * Execute multiple skills in sequence and merge their output into a
     * single unified result. Skills run in the order provided; each skill's
     * output is available to the next via the merged context.
     */
    private function combine(?array $input, array $context): SkillResult
    {
        $skillKeys = $input['skill_keys'] ?? [];
        $inputsMap = $input['inputs'] ?? [];
        $sharedInput = $input['shared_input'] ?? [];

        if (empty($skillKeys)) {
            return SkillResult::failed('skill_keys array is required for combine action');
        }

        /** @var SkillRegistryService $registry */
        $registry = app(SkillRegistryService::class);

        $results = [];
        $mergedOutput = [];
        $findings = [];
        $recommendations = [];
        $confidenceSum = 0;

        foreach ($skillKeys as $key) {
            try {
                $skill = $registry->resolve($key);
            } catch (\RuntimeException $e) {
                $findings[] = "Skill '{$key}' could not be resolved: {$e->getMessage()}";

                continue;
            }

            $skillInput = array_merge($sharedInput, $inputsMap[$key] ?? []);

            // Pass previous combined output as additional context
            $enrichedContext = array_merge($context, ['combined_output_so_far' => $mergedOutput]);

            $result = $skill->execute($skillInput, $enrichedContext);

            $results[$key] = [
                'status' => $result->status,
                'confidence' => $result->confidence,
                'output' => $result->output,
            ];

            $mergedOutput = array_merge($mergedOutput, [$key => $result->output]);
            $confidenceSum += $result->confidence;

            if (! empty($result->findings)) {
                $findings = array_merge($findings, array_map(fn ($f) => "[{$key}] {$f}", $result->findings));
            }
            if (! empty($result->recommendations)) {
                $recommendations = array_merge($recommendations, $result->recommendations);
            }
        }

        $executedCount = count($results);
        $avgConfidence = $executedCount > 0 ? round($confidenceSum / $executedCount, 1) : 0;

        return SkillResult::completed(
            [
                'executed_skills' => array_keys($results),
                'executed_count' => $executedCount,
                'requested_count' => count($skillKeys),
                'skill_results' => $results,
                'merged_output' => $mergedOutput,
                'average_confidence' => $avgConfidence,
            ],
            (float) $avgConfidence,
            $findings,
            $recommendations
        );
    }

    /**
     * Intelligently augment a target skill's input with contextual signals
     * before execution — e.g. injecting deployment metadata, recent task
     * history, or memory into the input array to improve skill output quality.
     */
    private function augment(?array $input, array $context): SkillResult
    {
        $targetKey = $input['target_skill'] ?? '';
        $baseInput = $input['base_input'] ?? [];

        if (empty($targetKey)) {
            return SkillResult::failed('target_skill is required for augment action');
        }

        /** @var SkillRegistryService $registry */
        $registry = app(SkillRegistryService::class);

        try {
            $skill = $registry->resolve($targetKey);
        } catch (\RuntimeException $e) {
            return SkillResult::failed("Target skill '{$targetKey}' could not be resolved: {$e->getMessage()}");
        }

        // Inject contextual signals into the input
        $deployment = $context['deployment'] ?? null;

        $augmented = array_merge($baseInput, array_filter([
            'deployment_id' => $deployment?->id,
            'deployment_mode' => $deployment?->deployment_mode,
            'organization_id' => $deployment?->organization_id,
            '_augmented_by' => 'superpowers',
            '_augmented_at' => now()->toIso8601String(),
        ]));

        // Execute the target skill with augmented input
        $result = $skill->execute($augmented, $context);

        return SkillResult::completed(
            [
                'target_skill' => $targetKey,
                'augmentation_keys' => array_diff(array_keys($augmented), array_keys($baseInput)),
                'result' => [
                    'status' => $result->status,
                    'confidence' => $result->confidence,
                    'output' => $result->output,
                    'findings' => $result->findings,
                    'recommendations' => $result->recommendations,
                ],
            ],
            $result->confidence,
            $result->findings,
            $result->recommendations
        );
    }
}
