<?php

namespace Tests\Unit\Skills;

use App\Skills\Core\ContextOptimizationSkill;
use Tests\TestCase;

class ContextOptimizationSkillTest extends TestCase
{
    private ContextOptimizationSkill $skill;

    protected function setUp(): void
    {
        parent::setUp();
        $this->skill = new ContextOptimizationSkill;
    }

    public function test_key_and_layer_are_correct(): void
    {
        $this->assertSame('context-optimization', $this->skill->key());
        $this->assertSame('core', $this->skill->layer());
    }

    public function test_optimize_ranks_context_items_by_relevance(): void
    {
        $result = $this->skill->execute([
            'action' => 'optimize',
            'goal' => 'Summarise last quarter sales performance for CFO review',
            'token_budget' => 2000,
            'context_items' => [
                ['type' => 'memory', 'content' => 'Q3 revenue hit $4.2M, up 18% YoY', 'relevance_score' => 0.9],
                ['type' => 'task',   'content' => 'Update slide deck with latest numbers', 'relevance_score' => 0.5],
                ['type' => 'memory', 'content' => 'User prefers bullet-point summaries', 'relevance_score' => 0.7],
            ],
        ]);

        $this->assertSame('completed', $result->status);
        $this->assertArrayHasKey('optimised_items', $result->output);
        $this->assertArrayHasKey('coverage_score', $result->output);
    }

    public function test_compress_reduces_token_footprint(): void
    {
        $result = $this->skill->execute([
            'action' => 'compress',
            'token_budget' => 200,
            'context_items' => [
                ['type' => 'memory', 'content' => str_repeat('Enterprise data. ', 50), 'token_estimate' => 100],
                ['type' => 'memory', 'content' => str_repeat('Sales context. ', 50),   'token_estimate' => 100],
                ['type' => 'task',   'content' => str_repeat('Task notes. ', 50),       'token_estimate' => 100],
                ['type' => 'memory', 'content' => str_repeat('User preferences. ', 50), 'token_estimate' => 100],
                ['type' => 'memory', 'content' => str_repeat('Previous results. ', 50), 'token_estimate' => 100],
            ],
        ]);

        $this->assertSame('completed', $result->status);
        $this->assertArrayHasKey('compressed_items', $result->output);
        $this->assertArrayHasKey('compression_ratio', $result->output);
    }

    public function test_unknown_action_returns_failed(): void
    {
        $result = $this->skill->execute(['action' => 'truncate_hard']);

        $this->assertSame('failed', $result->status);
    }
}
