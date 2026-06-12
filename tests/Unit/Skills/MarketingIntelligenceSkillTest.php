<?php

namespace Tests\Unit\Skills;

use App\Skills\Platform\MarketingIntelligenceSkill;
use Tests\TestCase;

class MarketingIntelligenceSkillTest extends TestCase
{
    private MarketingIntelligenceSkill $skill;

    protected function setUp(): void
    {
        parent::setUp();
        $this->skill = new MarketingIntelligenceSkill;
    }

    public function test_key_and_layer_are_correct(): void
    {
        $this->assertSame('marketing-intelligence', $this->skill->key());
        $this->assertSame('platform', $this->skill->layer());
    }

    // analyze_campaign: input key is 'campaign' (nested array)
    // output keys: campaign_name, kpis, scores, grade
    public function test_analyze_campaign_returns_scorecard(): void
    {
        $result = $this->skill->execute([
            'action' => 'analyze_campaign',
            'campaign' => [
                'name' => 'Q2 Product Launch',
                'impressions' => 50000,
                'clicks' => 2500,
                'conversions' => 125,
                'spend' => 5000.0,
                'revenue' => 18750.0,
            ],
        ]);

        $this->assertSame('completed', $result->status);
        $this->assertArrayHasKey('kpis', $result->output);
        $this->assertArrayHasKey('scores', $result->output);
        $this->assertArrayHasKey('grade', $result->output);
        $this->assertSame('Q2 Product Launch', $result->output['campaign_name']);
    }

    public function test_analyze_campaign_fails_without_campaign_array(): void
    {
        $result = $this->skill->execute(['action' => 'analyze_campaign']);

        $this->assertSame('failed', $result->status);
    }

    // content_brief: output is nested under 'brief' key
    public function test_content_brief_generates_structured_brief(): void
    {
        $result = $this->skill->execute([
            'action' => 'content_brief',
            'goal' => 'Drive sign-ups for the enterprise plan',
            'audience' => 'CTOs and engineering managers',
            'channel' => 'email',
        ]);

        $this->assertSame('completed', $result->status);
        $this->assertArrayHasKey('brief', $result->output);
        $this->assertArrayHasKey('goal', $result->output['brief']);
        $this->assertArrayHasKey('cta', $result->output['brief']);
    }

    public function test_content_brief_fails_without_goal(): void
    {
        $result = $this->skill->execute(['action' => 'content_brief']);

        $this->assertSame('failed', $result->status);
    }

    // segment_audience: input key is 'audience' (array of dicts)
    // output keys: total_audience, segments, segment_count, top_segment
    public function test_segment_audience_produces_clusters(): void
    {
        $result = $this->skill->execute([
            'action' => 'segment_audience',
            'audience' => [
                ['industry' => 'tech', 'size' => 'enterprise', 'revenue' => 5000000],
                ['industry' => 'retail', 'size' => 'smb', 'revenue' => 200000],
                ['industry' => 'tech', 'size' => 'startup', 'revenue' => 50000],
            ],
        ]);

        $this->assertSame('completed', $result->status);
        $this->assertArrayHasKey('segments', $result->output);
        $this->assertArrayHasKey('segment_count', $result->output);
        $this->assertGreaterThan(0, $result->output['segment_count']);
    }

    public function test_segment_audience_fails_with_empty_audience(): void
    {
        $result = $this->skill->execute(['action' => 'segment_audience', 'audience' => []]);

        $this->assertSame('failed', $result->status);
    }

    // measure_roi: input keys 'spend', 'revenue' (floats)
    // output keys: roi_pct, roas, spend_usd, total_investment_usd, status
    public function test_measure_roi_calculates_correctly(): void
    {
        $result = $this->skill->execute([
            'action' => 'measure_roi',
            'spend' => 10000.0,
            'revenue' => 35000.0,
        ]);

        $this->assertSame('completed', $result->status);
        $this->assertArrayHasKey('roi_pct', $result->output);
        $this->assertArrayHasKey('roas', $result->output);
        // ROI = (35000 - 10000) / 10000 * 100 = 250%
        $this->assertEqualsWithDelta(250.0, $result->output['roi_pct'], 1.0);
    }

    public function test_measure_roi_fails_with_zero_spend(): void
    {
        $result = $this->skill->execute([
            'action' => 'measure_roi',
            'spend' => 0.0,
            'revenue' => 1000.0,
        ]);

        $this->assertSame('failed', $result->status);
    }

    public function test_unknown_action_returns_failed(): void
    {
        $result = $this->skill->execute(['action' => 'bogus_action']);

        $this->assertSame('failed', $result->status);
    }
}
