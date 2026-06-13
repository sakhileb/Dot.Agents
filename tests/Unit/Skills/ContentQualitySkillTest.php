<?php

namespace Tests\Unit\Skills;

use App\Skills\Platform\ContentQualitySkill;
use Tests\TestCase;

class ContentQualitySkillTest extends TestCase
{
    private ContentQualitySkill $skill;

    protected function setUp(): void
    {
        parent::setUp();
        $this->skill = new ContentQualitySkill;
    }

    public function test_key_and_layer_are_correct(): void
    {
        $this->assertSame('content-quality', $this->skill->key());
        $this->assertSame('platform', $this->skill->layer());
    }

    public function test_validate_quality_scores_content(): void
    {
        $result = $this->skill->execute([
            'action' => 'validate_quality',
            'items' => [
                ['content' => 'Our AI-powered workforce platform helps enterprises hire, deploy, and manage digital workers at scale.'],
                ['content' => 'Automate repetitive tasks and unlock human creativity with Dot.Agents.'],
            ],
        ]);

        $this->assertSame('completed', $result->status);
        $this->assertArrayHasKey('quality_score', $result->output);
        $this->assertArrayHasKey('passed', $result->output);
    }

    public function test_validate_quality_fails_without_items(): void
    {
        $result = $this->skill->execute(['action' => 'validate_quality', 'items' => []]);

        $this->assertSame('failed', $result->status);
    }

    public function test_distribute_builds_channel_manifest(): void
    {
        $result = $this->skill->execute([
            'action' => 'distribute',
            'items' => [
                ['content' => 'Announcing our new AI agent marketplace launch.'],
            ],
            'channels' => ['twitter', 'linkedin', 'newsletter'],
        ]);

        $this->assertSame('completed', $result->status);
        $this->assertArrayHasKey('channels', $result->output);
    }

    public function test_unknown_action_returns_failed(): void
    {
        $result = $this->skill->execute(['action' => 'publish_immediately']);

        $this->assertSame('failed', $result->status);
    }
}
