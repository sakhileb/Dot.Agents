<?php

namespace App\Skills\Platform;

use App\Skills\DTOs\SkillResult;

/**
 * MassContentGenerationSkill — backward-compatible router (Layer 5 — Platform Intelligence)
 *
 * Kept for compatibility with existing DB records and API consumers.
 * All logic has been extracted into focused skill classes:
 *   - ContentBatchSkill   (key: content-batch)   — generate_batch, template
 *   - ContentQualitySkill (key: content-quality) — validate_quality, distribute
 *
 * New deployments should reference the focused skills directly.
 */
class MassContentGenerationSkill extends ContentGenerationHelper
{
    private ContentBatchSkill $batch;

    private ContentQualitySkill $quality;

    public function __construct()
    {
        $this->batch = new ContentBatchSkill;
        $this->quality = new ContentQualitySkill;
    }

    public function key(): string
    {
        return 'mass-content-generation';
    }

    public function layer(): string
    {
        return 'platform';
    }

    /**
     * Delegates to ContentBatchSkill or ContentQualitySkill based on action.
     *
     * Actions:
     *   generate_batch, template → ContentBatchSkill
     *   validate_quality, distribute → ContentQualitySkill
     */
    public function execute(array $input, array $context = []): SkillResult
    {
        $action = $input['action'] ?? 'generate_batch';

        return match ($action) {
            'generate_batch', 'template' => $this->batch->execute($input, $context),
            default => $this->quality->execute($input, $context),
        };
    }
}
