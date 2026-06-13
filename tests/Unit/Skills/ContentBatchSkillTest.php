<?php

namespace Tests\Unit\Skills;

use App\Skills\Platform\ContentBatchSkill;
use Tests\TestCase;

class ContentBatchSkillTest extends TestCase
{
    private ContentBatchSkill $skill;

    protected function setUp(): void
    {
        parent::setUp();
        $this->skill = new ContentBatchSkill;
    }

    public function test_key_and_layer_are_correct(): void
    {
        $this->assertSame('content-batch', $this->skill->key());
        $this->assertSame('platform', $this->skill->layer());
    }

    public function test_generate_batch_returns_multiple_variants(): void
    {
        $result = $this->skill->execute([
            'action' => 'generate_batch',
            'template' => 'Introducing {product} — the future of {industry}.',
            'variables' => [
                ['product' => 'Dot.Agents', 'industry' => 'enterprise AI'],
                ['product' => 'Dot.Agents', 'industry' => 'workforce automation'],
                ['product' => 'Dot.Agents', 'industry' => 'digital transformation'],
            ],
        ]);

        $this->assertSame('completed', $result->status);
        $this->assertArrayHasKey('items', $result->output);
        $this->assertArrayHasKey('generated_count', $result->output);
        $this->assertGreaterThan(0, $result->output['generated_count']);
    }

    public function test_generate_batch_fails_without_template(): void
    {
        $result = $this->skill->execute(['action' => 'generate_batch', 'template' => '', 'variables' => []]);

        $this->assertSame('failed', $result->status);
    }

    public function test_template_returns_scaffold(): void
    {
        $result = $this->skill->execute([
            'action' => 'template',
            'example' => 'Introducing Dot.Agents — the AI workforce platform for enterprise teams.',
        ]);

        $this->assertSame('completed', $result->status);
        $this->assertArrayHasKey('template', $result->output);
    }

    public function test_unknown_action_returns_failed(): void
    {
        $result = $this->skill->execute(['action' => 'publish_now']);

        $this->assertSame('failed', $result->status);
    }
}
