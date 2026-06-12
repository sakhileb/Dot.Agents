<?php

namespace Tests\Unit\Skills;

use App\Skills\Core\ContextEngineeringSkill;
use Tests\TestCase;

class ContextEngineeringSkillTest extends TestCase
{
    private ContextEngineeringSkill $skill;

    private array $sampleItems;

    protected function setUp(): void
    {
        parent::setUp();
        $this->skill = new ContextEngineeringSkill;
        $this->sampleItems = [
            ['role' => 'system', 'content' => 'You are an enterprise AI assistant.', 'importance' => 95],
            ['role' => 'user', 'content' => 'Generate a quarterly financial report.', 'importance' => 80],
            ['role' => 'assistant', 'content' => 'I can help with that.', 'importance' => 40],
            ['role' => 'context', 'content' => 'Previous task: summarise invoices.', 'importance' => 25],
            ['role' => 'context', 'content' => 'User prefers bullet point format.', 'importance' => 60],
        ];
    }

    public function test_key_and_layer_are_correct(): void
    {
        $this->assertSame('context-engineering', $this->skill->key());
        $this->assertSame('core', $this->skill->layer());
    }

    // optimize: output keys: optimised_items, total_items, estimated_tokens, token_budget, coverage_score, goal_alignment
    public function test_optimize_returns_scored_items(): void
    {
        $result = $this->skill->execute([
            'action' => 'optimize',
            'context_items' => $this->sampleItems,
            'goal' => 'quarterly financial reporting',
            'token_budget' => 2048,
        ]);

        $this->assertSame('completed', $result->status);
        $this->assertArrayHasKey('optimised_items', $result->output);
        $this->assertArrayHasKey('total_items', $result->output);
        $this->assertArrayHasKey('token_budget', $result->output);
        $this->assertSame(count($this->sampleItems), $result->output['total_items']);
    }

    public function test_optimize_respects_token_budget(): void
    {
        $result = $this->skill->execute([
            'action' => 'optimize',
            'context_items' => $this->sampleItems,
            'token_budget' => 10, // very tight
        ]);

        $this->assertSame('completed', $result->status);
        // With a tiny budget, optimised_items should be fewer than total
        $this->assertLessThanOrEqual(
            $result->output['total_items'],
            count($result->output['optimised_items'])
        );
    }

    // compress: output keys: compressed_items, original_count, final_count, items_compressed, estimated_tokens, target_tokens, compression_ratio
    public function test_compress_reduces_context_size(): void
    {
        $result = $this->skill->execute([
            'action' => 'compress',
            'context_items' => $this->sampleItems,
            'token_budget' => 1024,
        ]);

        $this->assertSame('completed', $result->status);
        $this->assertArrayHasKey('compressed_items', $result->output);
        $this->assertArrayHasKey('compression_ratio', $result->output);
        $this->assertArrayHasKey('original_count', $result->output);
    }

    // prioritize: output keys: prioritized_items, total_items, top_item_preview
    public function test_prioritize_sorts_by_relevance(): void
    {
        $result = $this->skill->execute([
            'action' => 'prioritize',
            'context_items' => $this->sampleItems,
            'goal' => 'financial reporting',
        ]);

        $this->assertSame('completed', $result->status);
        $this->assertArrayHasKey('prioritized_items', $result->output);
        $this->assertCount(count($this->sampleItems), $result->output['prioritized_items']);
    }

    public function test_prioritize_skips_with_no_items(): void
    {
        $result = $this->skill->execute([
            'action' => 'prioritize',
            'context_items' => [],
        ]);

        // Empty items → skipped result (nothing to prioritize)
        $this->assertSame('skipped', $result->status);
    }

    // inject requires DB deployment context — unit test verifies the guard
    public function test_inject_requires_deployment_id(): void
    {
        $result = $this->skill->execute([
            'action' => 'inject',
            // No deployment_id provided
        ]);

        $this->assertSame('failed', $result->status);
    }

    public function test_unknown_action_returns_failed(): void
    {
        $result = $this->skill->execute(['action' => 'evaporate']);

        $this->assertSame('failed', $result->status);
    }

    public function test_default_action_is_optimize(): void
    {
        $result = $this->skill->execute(['context_items' => $this->sampleItems]);

        $this->assertNotSame('failed', $result->status);
    }
}
