<?php

namespace Tests\Unit\Skills;

use App\Skills\Meta\SkillCombinatorSkill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SkillCombinatorSkillTest extends TestCase
{
    use RefreshDatabase;

    private SkillCombinatorSkill $skill;

    protected function setUp(): void
    {
        parent::setUp();
        $this->skill = app(SkillCombinatorSkill::class);
    }

    public function test_key_and_layer_are_correct(): void
    {
        $this->assertSame('skill-combinator', $this->skill->key());
        $this->assertSame('meta', $this->skill->layer());
    }

    public function test_combine_merges_multiple_skill_outputs(): void
    {
        $result = $this->skill->execute([
            'action' => 'combine',
            'skill_keys' => ['seo-optimization', 'content-batch'],
            'goal' => 'Generate SEO-optimised blog posts at scale',
        ]);

        $this->assertSame('completed', $result->status);
        $this->assertArrayHasKey('executed_skills', $result->output);
        $this->assertArrayHasKey('merged_output', $result->output);
    }

    public function test_combine_fails_without_skills_list(): void
    {
        $result = $this->skill->execute(['action' => 'combine', 'skill_keys' => []]);

        $this->assertSame('failed', $result->status);
    }

    public function test_augment_returns_enhanced_skill_spec(): void
    {
        $result = $this->skill->execute([
            'action' => 'augment',
            'target_skill' => 'content-batch',
            'new_action' => 'tone_control',
            'handler_description' => 'Adjust tone of generated content based on audience segment',
        ]);

        $this->assertSame('completed', $result->status);
        $this->assertArrayHasKey('target_skill', $result->output);
        $this->assertArrayHasKey('augmentation_keys', $result->output);
    }

    public function test_unknown_action_returns_failed(): void
    {
        $result = $this->skill->execute(['action' => 'deploy_chain']);

        $this->assertSame('failed', $result->status);
    }
}
