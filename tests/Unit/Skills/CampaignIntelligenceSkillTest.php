<?php

namespace Tests\Unit\Skills;

use App\Skills\Platform\CampaignIntelligenceSkill;
use Tests\TestCase;

class CampaignIntelligenceSkillTest extends TestCase
{
    private CampaignIntelligenceSkill $skill;

    protected function setUp(): void
    {
        parent::setUp();
        $this->skill = new CampaignIntelligenceSkill;
    }

    public function test_key_and_layer_are_correct(): void
    {
        $this->assertSame('campaign-intelligence', $this->skill->key());
        $this->assertSame('platform', $this->skill->layer());
    }

    public function test_analyze_campaign_returns_performance_breakdown(): void
    {
        $result = $this->skill->execute([
            'action' => 'analyze_campaign',
            'campaign' => 'Enterprise AI Platform Launch Q3',
            'channels' => ['linkedin', 'email'],
            'kpis' => ['impressions' => 45000, 'clicks' => 1200, 'conversions' => 48, 'spend_usd' => 5000, 'revenue_usd' => 18000],
        ]);

        $this->assertSame('completed', $result->status);
        $this->assertArrayHasKey('kpis', $result->output);
        $this->assertArrayHasKey('grade', $result->output);
    }

    public function test_analyze_campaign_returns_grade_field(): void
    {
        $result = $this->skill->execute(['action' => 'analyze_campaign', 'campaign' => 'Test Campaign']);

        $this->assertSame('completed', $result->status);
        $this->assertArrayHasKey('grade', $result->output);
    }

    public function test_content_brief_returns_structured_brief(): void
    {
        $result = $this->skill->execute([
            'action' => 'content_brief',
            'goal' => 'Drive sign-ups for enterprise free trial',
            'audience' => 'CTOs at mid-market companies',
            'channels' => ['linkedin', 'blog'],
        ]);

        $this->assertSame('completed', $result->status);
        $this->assertArrayHasKey('brief', $result->output);
    }

    public function test_unknown_action_returns_failed(): void
    {
        $result = $this->skill->execute(['action' => 'send_campaign']);

        $this->assertSame('failed', $result->status);
    }
}
