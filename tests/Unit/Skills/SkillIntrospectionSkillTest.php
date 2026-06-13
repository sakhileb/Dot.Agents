<?php

namespace Tests\Unit\Skills;

use App\Models\AgentDeployment;
use App\Skills\Meta\SkillIntrospectionSkill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SkillIntrospectionSkillTest extends TestCase
{
    use RefreshDatabase;

    private SkillIntrospectionSkill $skill;

    protected function setUp(): void
    {
        parent::setUp();
        $this->skill = new SkillIntrospectionSkill;
    }

    public function test_key_and_layer_are_correct(): void
    {
        $this->assertSame('skill-introspection', $this->skill->key());
        $this->assertSame('meta', $this->skill->layer());
    }

    public function test_introspect_returns_capability_summary_for_deployment(): void
    {
        $deployment = AgentDeployment::factory()->create();

        $result = $this->skill->execute(
            ['action' => 'introspect', 'include_metadata' => true],
            ['deployment' => $deployment]
        );

        $this->assertSame('completed', $result->status);
        $this->assertArrayHasKey('skill_count', $result->output);
        $this->assertArrayHasKey('capability_coverage', $result->output);
    }

    public function test_extend_fails_gracefully_without_active_skill(): void
    {
        $deployment = AgentDeployment::factory()->create();

        $result = $this->skill->execute(
            ['action' => 'extend', 'skill_key' => 'nonexistent-skill-key'],
            ['deployment' => $deployment]
        );

        // Skill not found in catalogue — should fail gracefully
        $this->assertSame('failed', $result->status);
    }

    public function test_unknown_action_returns_failed(): void
    {
        $result = $this->skill->execute(['action' => 'self_destruct']);

        $this->assertSame('failed', $result->status);
    }
}
