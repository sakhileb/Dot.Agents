<?php

namespace App\Skills\Meta;

use App\Services\AI\SkillRegistryService;
use App\Skills\BaseSkill;
use App\Skills\DTOs\SkillResult;

/**
 * SkillCombinatorSkill — skill composition and input augmentation (Layer 6 — Meta-Agent)
 *
 * Handles the two in-memory meta actions extracted from SuperpowersSkill:
 *   combine – execute multiple skills sequentially and merge their outputs
 *   augment – enhance a skill's input with contextual intelligence before execution
 *
 * The introspect/extend actions (which require DB access) are in SkillIntrospectionSkill.
 */
class SkillCombinatorSkill extends BaseSkill
{
    public function key(): string
    {
        return 'skill-combinator';
    }

    public function layer(): string
    {
        return 'meta';
    }

    public function execute(array $input, array $context = []): SkillResult
    {
        $action = $input['action'] ?? 'combine';

        return match ($action) {
            'combine' => $this->combine($input, $context),
            'augment' => $this->augment($input, $context),
            default => SkillResult::failed("Unknown skill-combinator action: [{$action}]"),
        };
    }

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

        $deployment = $context['deployment'] ?? null;

        $augmented = array_merge($baseInput, array_filter([
            'deployment_id' => $deployment?->id,
            'deployment_mode' => $deployment?->deployment_mode,
            'organization_id' => $deployment?->organization_id,
            '_augmented_by' => 'superpowers',
            '_augmented_at' => now()->toIso8601String(),
        ]));

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
