<?php

namespace Tests\Unit\Skills;

use App\Skills\Platform\AudienceIntelligenceSkill;
use Tests\TestCase;

class AudienceIntelligenceSkillTest extends TestCase
{
    private AudienceIntelligenceSkill $skill;

    protected function setUp(): void
    {
        parent::setUp();
        $this->skill = new AudienceIntelligenceSkill;
    }

    public function test_key_and_layer_are_correct(): void
    {
        $this->assertSame('audience-intelligence', $this->skill->key());
        $this->assertSame('platform', $this->skill->layer());
    }

    public function test_segment_audience_returns_segments(): void
    {
        $result = $this->skill->execute([
            'action' => 'segment_audience',
            'audience' => [
                ['role' => 'CTO',             'company_size' => 'enterprise', 'industry' => 'fintech'],
                ['role' => 'VP Engineering',   'company_size' => 'mid-market', 'industry' => 'saas'],
                ['role' => 'Software Engineer', 'company_size' => 'startup',    'industry' => 'ecommerce'],
            ],
        ]);

        $this->assertSame('completed', $result->status);
        $this->assertArrayHasKey('segments', $result->output);
        $this->assertArrayHasKey('segment_count', $result->output);
    }

    public function test_segment_audience_fails_without_audience_data(): void
    {
        $result = $this->skill->execute(['action' => 'segment_audience', 'audience' => []]);

        $this->assertSame('failed', $result->status);
    }

    public function test_measure_roi_returns_roi_metrics(): void
    {
        $result = $this->skill->execute([
            'action' => 'measure_roi',
            'campaign' => 'Q3 Enterprise Push',
            'spend' => 15000,
            'revenue' => 72000,
        ]);

        $this->assertSame('completed', $result->status);
        $this->assertArrayHasKey('roi_pct', $result->output);
        $this->assertArrayHasKey('roas', $result->output);
    }

    public function test_unknown_action_returns_failed(): void
    {
        $result = $this->skill->execute(['action' => 'track_users_globally']);

        $this->assertSame('failed', $result->status);
    }
}
