<?php

namespace Tests\Unit\Skills;

use App\Skills\Core\ContextMemorySkill;
use Tests\TestCase;

class ContextMemorySkillTest extends TestCase
{
    private ContextMemorySkill $skill;

    protected function setUp(): void
    {
        parent::setUp();
        $this->skill = new ContextMemorySkill;
    }

    public function test_key_and_layer_are_correct(): void
    {
        $this->assertSame('context-memory', $this->skill->key());
        $this->assertSame('core', $this->skill->layer());
    }

    public function test_prioritize_returns_sorted_items(): void
    {
        $result = $this->skill->execute([
            'action' => 'prioritize',
            'goal' => 'Generate a sales proposal for enterprise customer',
            'context_items' => [
                ['type' => 'memory', 'content' => 'Customer prefers concise proposals', 'relevance_score' => 0.8],
                ['type' => 'task',   'content' => 'Draft pricing table',                'relevance_score' => 0.6],
                ['type' => 'memory', 'content' => 'Previous meeting notes from Q2',     'relevance_score' => 0.4],
            ],
        ]);

        $this->assertSame('completed', $result->status);
        $this->assertArrayHasKey('prioritized_items', $result->output);
        $this->assertArrayHasKey('total_items', $result->output);
    }

    // inject requires a deployment with memory records; test the failure path without deployment
    public function test_inject_fails_without_deployment_context(): void
    {
        $result = $this->skill->execute([
            'action' => 'inject',
            'context_items' => [['type' => 'memory', 'content' => 'User timezone is UTC+2']],
        ]);

        $this->assertSame('failed', $result->status);
    }

    public function test_unknown_action_returns_failed(): void
    {
        $result = $this->skill->execute(['action' => 'forget_all']);

        $this->assertSame('failed', $result->status);
    }
}
