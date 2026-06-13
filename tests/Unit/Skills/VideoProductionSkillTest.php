<?php

namespace Tests\Unit\Skills;

use App\Skills\Platform\VideoProductionSkill;
use Tests\TestCase;

class VideoProductionSkillTest extends TestCase
{
    private VideoProductionSkill $skill;

    protected function setUp(): void
    {
        parent::setUp();
        $this->skill = new VideoProductionSkill;
    }

    public function test_key_and_layer_are_correct(): void
    {
        $this->assertSame('video-production', $this->skill->key());
        $this->assertSame('platform', $this->skill->layer());
    }

    public function test_scene_breakdown_parses_scenes(): void
    {
        $result = $this->skill->execute([
            'action' => 'scene_breakdown',
            'script' => "Introduction.\n\nCore feature walkthrough.\n\nCall to action.",
        ]);

        $this->assertSame('completed', $result->status);
        $this->assertArrayHasKey('scenes', $result->output);
        $this->assertArrayHasKey('scene_count', $result->output);
        $this->assertGreaterThan(0, $result->output['scene_count']);
    }

    public function test_scene_breakdown_fails_without_script(): void
    {
        $result = $this->skill->execute(['action' => 'scene_breakdown', 'script' => '']);

        $this->assertSame('failed', $result->status);
    }

    public function test_render_config_produces_valid_structure(): void
    {
        $result = $this->skill->execute([
            'action' => 'render_config',
            'scenes' => [
                ['scene_number' => 1, 'narration' => 'Intro', 'estimated_duration_sec' => 5],
                ['scene_number' => 2, 'narration' => 'Main', 'estimated_duration_sec' => 15],
            ],
            'fps' => 30,
        ]);

        $this->assertSame('completed', $result->status);
        $this->assertArrayHasKey('render_config', $result->output);
        $this->assertArrayHasKey('sequences', $result->output['render_config']);
    }

    public function test_unknown_action_returns_failed(): void
    {
        $result = $this->skill->execute(['action' => 'nonexistent']);

        $this->assertSame('failed', $result->status);
    }
}
