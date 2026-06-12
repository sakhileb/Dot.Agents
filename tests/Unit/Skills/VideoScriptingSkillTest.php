<?php

namespace Tests\Unit\Skills;

use App\Skills\Platform\VideoScriptingSkill;
use Tests\TestCase;

class VideoScriptingSkillTest extends TestCase
{
    private VideoScriptingSkill $skill;

    protected function setUp(): void
    {
        parent::setUp();
        $this->skill = new VideoScriptingSkill;
    }

    public function test_key_and_layer_are_correct(): void
    {
        $this->assertSame('video-scripting', $this->skill->key());
        $this->assertSame('platform', $this->skill->layer());
    }

    // script: requires 'topic'; output: topic, format, total_duration_sec, sections, section_count
    public function test_script_generates_timed_segments(): void
    {
        $result = $this->skill->execute([
            'action' => 'script',
            'topic' => 'Introducing our AI workforce platform to enterprise CTOs',
            'format' => 'explainer',
            'tone' => 'professional',
        ]);

        $this->assertSame('completed', $result->status);
        $this->assertArrayHasKey('sections', $result->output);
        $this->assertArrayHasKey('total_duration_sec', $result->output);
        $this->assertArrayHasKey('format', $result->output);
        $this->assertSame('explainer', $result->output['format']);
    }

    public function test_script_fails_without_topic(): void
    {
        $result = $this->skill->execute(['action' => 'script', 'topic' => '']);

        $this->assertSame('failed', $result->status);
    }

    public function test_script_respects_reel_format_duration(): void
    {
        $result = $this->skill->execute([
            'action' => 'script',
            'topic' => 'Quick product demo for Instagram Reels',
            'format' => 'reel',
        ]);

        $this->assertSame('completed', $result->status);
        // Reel format = 30 seconds max
        $this->assertLessThanOrEqual(30, $result->output['total_duration_sec']);
    }

    // storyboard: requires 'topic'; output: topic, format, frame_count, frames
    public function test_storyboard_returns_visual_frames(): void
    {
        $result = $this->skill->execute([
            'action' => 'storyboard',
            'topic' => 'Enterprise AI agent product demo',
            'format' => 'standard',
            'scene_count' => 4,
        ]);

        $this->assertSame('completed', $result->status);
        $this->assertArrayHasKey('frames', $result->output);
        $this->assertArrayHasKey('frame_count', $result->output);
        $this->assertGreaterThan(0, $result->output['frame_count']);
    }

    // scene_breakdown: requires 'script'; output: scene_count, total_estimated_duration_sec, scenes
    public function test_scene_breakdown_parses_scenes(): void
    {
        $result = $this->skill->execute([
            'action' => 'scene_breakdown',
            'script' => "Introduction to the platform.\n\nCore feature walkthrough for enterprise teams.\n\nCall to action and pricing overview.",
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

    // render_config: requires 'scenes' array; output: render_config (with composition/sequences/output/meta)
    public function test_render_config_produces_remotion_compatible_json(): void
    {
        $scenes = [
            ['scene_number' => 1, 'narration' => 'Opening title card', 'estimated_duration_sec' => 5],
            ['scene_number' => 2, 'narration' => 'Main content section', 'estimated_duration_sec' => 15],
            ['scene_number' => 3, 'narration' => 'Closing call to action', 'estimated_duration_sec' => 5],
        ];

        $result = $this->skill->execute([
            'action' => 'render_config',
            'scenes' => $scenes,
            'fps' => 30,
            'width' => 1920,
            'height' => 1080,
        ]);

        $this->assertSame('completed', $result->status);
        $this->assertArrayHasKey('render_config', $result->output);
        $this->assertArrayHasKey('composition', $result->output['render_config']);
        $this->assertArrayHasKey('sequences', $result->output['render_config']);
        $this->assertSame(30, $result->output['fps']);
    }

    public function test_unknown_action_returns_failed(): void
    {
        $result = $this->skill->execute(['action' => 'invalid_video_action']);

        $this->assertSame('failed', $result->status);
    }

    public function test_default_action_is_script(): void
    {
        $result = $this->skill->execute(['topic' => 'A quick demo video']);

        // script is the default — with a topic provided it should complete
        $this->assertSame('completed', $result->status);
    }
}
