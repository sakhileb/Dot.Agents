<?php

namespace Tests\Unit\Skills;

use App\Skills\Platform\MassContentGenerationSkill;
use Tests\TestCase;

class MassContentGenerationSkillTest extends TestCase
{
    private MassContentGenerationSkill $skill;

    protected function setUp(): void
    {
        parent::setUp();
        $this->skill = new MassContentGenerationSkill;
    }

    public function test_key_and_layer_are_correct(): void
    {
        $this->assertSame('mass-content-generation', $this->skill->key());
        $this->assertSame('platform', $this->skill->layer());
    }

    // template: input key is 'example' (or 'content'), NOT 'template'
    // output keys: template, content_type, variable_count, variables, sample_variable_set, note
    public function test_template_builds_from_example(): void
    {
        $result = $this->skill->execute([
            'action' => 'template',
            'example' => 'Hello Alice from Acme Corp, your plan is Enterprise.',
            'define_variables' => ['name', 'company', 'plan'],
        ]);

        $this->assertSame('completed', $result->status);
        $this->assertArrayHasKey('template', $result->output);
        $this->assertArrayHasKey('variable_count', $result->output);
    }

    public function test_template_fails_without_example(): void
    {
        $result = $this->skill->execute(['action' => 'template']);

        $this->assertSame('failed', $result->status);
    }

    // generate_batch: uses {var} placeholder syntax (not {{var}})
    // output keys: generated_count, requested_count, error_count, success_rate_pct, items, template_variables
    public function test_generate_batch_produces_n_items(): void
    {
        $result = $this->skill->execute([
            'action' => 'generate_batch',
            'template' => 'Welcome {name} from {company}! Your plan is {plan}.',
            'variables' => [
                ['name' => 'Alice', 'company' => 'Acme Corp', 'plan' => 'Enterprise'],
                ['name' => 'Bob', 'company' => 'Globex', 'plan' => 'Professional'],
                ['name' => 'Carol', 'company' => 'Initech', 'plan' => 'Starter'],
            ],
        ]);

        $this->assertSame('completed', $result->status);
        $this->assertArrayHasKey('items', $result->output);
        $this->assertArrayHasKey('generated_count', $result->output);
        $this->assertSame(3, $result->output['generated_count']);
    }

    public function test_generate_batch_respects_max_batch_size(): void
    {
        $variables = array_fill(0, 501, ['name' => 'User']);

        $result = $this->skill->execute([
            'action' => 'generate_batch',
            'template' => 'Hello {name}',
            'variables' => $variables,
        ]);

        $this->assertSame('failed', $result->status);
    }

    public function test_generate_batch_fails_without_template(): void
    {
        $result = $this->skill->execute([
            'action' => 'generate_batch',
            'variables' => [['name' => 'Test']],
        ]);

        $this->assertSame('failed', $result->status);
    }

    // validate_quality: output keys: uniqueness_ratio, passed, item_count, quality_score
    public function test_validate_quality_detects_duplicates(): void
    {
        $result = $this->skill->execute([
            'action' => 'validate_quality',
            'items' => [
                'Welcome Alice from Acme Corp',
                'Welcome Bob from Globex',
                'Welcome Alice from Acme Corp', // exact duplicate
            ],
        ]);

        $this->assertSame('completed', $result->status);
        $this->assertArrayHasKey('uniqueness_ratio', $result->output);
        $this->assertArrayHasKey('passed', $result->output);
        // 2/3 unique = 0.667 which is below the 0.80 minimum threshold
        $this->assertFalse($result->output['passed']);
    }

    public function test_validate_quality_passes_unique_batch(): void
    {
        $result = $this->skill->execute([
            'action' => 'validate_quality',
            'items' => [
                'Welcome Alice from Acme Corp to the Enterprise plan!',
                'Welcome Bob from Globex to the Professional plan!',
                'Welcome Carol from Initech to the Starter plan!',
                'Welcome Dave from Megacorp to the Enterprise plan!',
                'Welcome Eve from Techco to the Professional plan!',
            ],
        ]);

        $this->assertSame('completed', $result->status);
        $this->assertTrue($result->output['passed']);
    }

    // distribute: items must be arrays with at least a 'content' key
    // output keys: source_item_count, channel_count, channels, manifest, estimated_publish_items
    public function test_distribute_creates_channel_manifest(): void
    {
        $result = $this->skill->execute([
            'action' => 'distribute',
            'items' => [
                ['content' => 'Item A — email version', 'type' => 'onboarding'],
                ['content' => 'Item B — email version', 'type' => 'onboarding'],
            ],
            'channels' => ['email', 'slack', 'sms'],
        ]);

        $this->assertSame('completed', $result->status);
        $this->assertArrayHasKey('manifest', $result->output);
        $this->assertArrayHasKey('channel_count', $result->output);
        $this->assertSame(3, $result->output['channel_count']);
    }

    public function test_unknown_action_returns_failed(): void
    {
        $result = $this->skill->execute(['action' => 'blast_everything']);

        $this->assertSame('failed', $result->status);
    }
}
