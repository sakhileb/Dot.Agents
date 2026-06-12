<?php

namespace App\Skills\Platform;

use App\Skills\DTOs\SkillResult;

/**
 * VideoScriptingSkill — backward-compatible router (Layer 5 — Platform Intelligence)
 *
 * Kept for compatibility with existing DB records and API consumers.
 * All logic has been extracted into focused skill classes:
 *   - VideoScriptWriterSkill (key: video-script-writer) — script, storyboard
 *   - VideoProductionSkill   (key: video-production)    — scene_breakdown, render_config
 *
 * New deployments should reference the focused skills directly.
 */
class VideoScriptingSkill extends VideoScriptHelper
{
    private VideoScriptWriterSkill $writer;

    private VideoProductionSkill $production;

    public function __construct()
    {
        $this->writer = new VideoScriptWriterSkill;
        $this->production = new VideoProductionSkill;
    }

    public function key(): string
    {
        return 'video-scripting';
    }

    public function layer(): string
    {
        return 'platform';
    }

    /**
     * Delegates to VideoScriptWriterSkill or VideoProductionSkill based on action.
     *
     * Actions:
     *   script, storyboard          → VideoScriptWriterSkill
     *   scene_breakdown, render_config → VideoProductionSkill
     */
    public function execute(array $input, array $context = []): SkillResult
    {
        $action = $input['action'] ?? 'script';

        return match ($action) {
            'scene_breakdown', 'render_config' => $this->production->execute($input, $context),
            default => $this->writer->execute($input, $context),
        };
    }
}
