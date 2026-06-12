<?php

namespace Tests\Unit\Skills;

use App\Skills\Meta\SuperpowersSkill;
use Tests\TestCase;

/**
 * Unit tests for SuperpowersSkill.
 *
 * introspect and extend require DB deployment records, so those paths are tested
 * via guard behaviour (missing required context). The combine action operates
 * on live SkillRegistryService (in-memory, no DB) so is tested here end-to-end.
 */
class SuperpowersSkillTest extends TestCase
{
    private SuperpowersSkill $skill;

    protected function setUp(): void
    {
        parent::setUp();
        $this->skill = new SuperpowersSkill;
    }

    public function test_key_and_layer_are_correct(): void
    {
        $this->assertSame('superpowers', $this->skill->key());
        $this->assertSame('meta', $this->skill->layer());
    }

    // introspect requires deployment_id (DB context) — verify guard
    public function test_introspect_fails_without_deployment_context(): void
    {
        $result = $this->skill->execute(['action' => 'introspect']);

        $this->assertSame('failed', $result->status);
        $this->assertStringContainsString('Deployment context', $result->output['error']);
    }

    // extend guards
    public function test_extend_fails_without_skill_key(): void
    {
        $result = $this->skill->execute([
            'action' => 'extend',
            'deployment_id' => 1,
        ]);

        $this->assertSame('failed', $result->status);
    }

    public function test_extend_fails_without_deployment_id(): void
    {
        $result = $this->skill->execute([
            'action' => 'extend',
            'skill_key' => 'seo-optimization',
        ]);

        $this->assertSame('failed', $result->status);
    }

    // combine: uses SkillRegistryService (in-memory, no DB required)
    public function test_combine_executes_single_skill(): void
    {
        $result = $this->skill->execute([
            'action' => 'combine',
            'skill_keys' => ['seo-optimization'],
            'input' => [
                'title' => 'Enterprise AI Agents for Modern Businesses',
                'content' => str_repeat('AI agents help enterprise teams. ', 30),
            ],
        ]);

        $this->assertSame('completed', $result->status);
    }

    public function test_combine_fails_without_skill_keys(): void
    {
        $result = $this->skill->execute([
            'action' => 'combine',
            'input' => ['content' => 'test'],
        ]);

        $this->assertSame('failed', $result->status);
    }

    public function test_combine_fails_with_empty_skill_keys(): void
    {
        $result = $this->skill->execute([
            'action' => 'combine',
            'skill_keys' => [],
            'input' => ['content' => 'test'],
        ]);

        $this->assertSame('failed', $result->status);
    }

    // augment guards
    public function test_augment_fails_without_target_skill(): void
    {
        $result = $this->skill->execute([
            'action' => 'augment',
            'input' => ['content' => 'test'],
        ]);

        $this->assertSame('failed', $result->status);
    }

    public function test_unknown_action_returns_failed(): void
    {
        $result = $this->skill->execute(['action' => 'fly']);

        $this->assertSame('failed', $result->status);
    }

    public function test_default_action_fails_without_deployment_context(): void
    {
        // Default is introspect which requires deployment_id
        $result = $this->skill->execute([]);

        $this->assertSame('failed', $result->status);
    }
}
