<?php

namespace App\Skills\Platform;

use App\Skills\DTOs\SkillResult;

/**
 * CampaignIntelligenceSkill — campaign analysis and content briefs (Layer 5 — Platform Intelligence)
 *
 * Handles the two campaign-focused marketing actions extracted from MarketingIntelligenceSkill:
 *   analyze_campaign – scorecard a campaign's key performance indicators
 *   content_brief    – generate a structured content brief from a marketing goal
 *
 * Shares channel structure and CTA helpers with AudienceIntelligenceSkill via MarketingHelper.
 */
class CampaignIntelligenceSkill extends MarketingHelper
{
    public function key(): string
    {
        return 'campaign-intelligence';
    }

    public function layer(): string
    {
        return 'platform';
    }

    public function execute(array $input, array $context = []): SkillResult
    {
        $action = $input['action'] ?? 'analyze_campaign';

        return match ($action) {
            'analyze_campaign' => $this->analyzeCampaign($input),
            'content_brief' => $this->generateContentBrief($input),
            default => SkillResult::failed("Unknown campaign-intelligence action: [{$action}]"),
        };
    }

    private function analyzeCampaign(array $input): SkillResult
    {
        $campaign = $input['campaign'] ?? [];

        if (empty($campaign)) {
            return SkillResult::failed('Campaign metrics array is required for analyze_campaign');
        }

        $impressions = (int) ($campaign['impressions'] ?? 0);
        $clicks = (int) ($campaign['clicks'] ?? 0);
        $conversions = (int) ($campaign['conversions'] ?? 0);
        $spend = (float) ($campaign['spend'] ?? 0);
        $revenue = (float) ($campaign['revenue'] ?? 0);

        $ctr = $impressions > 0 ? round($clicks / $impressions * 100, 2) : 0;
        $convRate = $clicks > 0 ? round($conversions / $clicks * 100, 2) : 0;
        $cpc = $clicks > 0 ? round($spend / $clicks, 4) : 0;
        $cpa = $conversions > 0 ? round($spend / $conversions, 2) : 0;
        $roas = $spend > 0 ? round($revenue / $spend, 2) : 0;

        $ctrScore = $this->clamp($ctr >= 2.0 ? 100 : ($ctr / 2.0 * 100));
        $convScore = $this->clamp($convRate >= 3.0 ? 100 : ($convRate / 3.0 * 100));
        $roasScore = $this->clamp($roas >= 3.0 ? 100 : ($roas / 3.0 * 100));
        $healthScore = round(($ctrScore * 0.3) + ($convScore * 0.4) + ($roasScore * 0.3), 1);

        $findings = [];
        $recommendations = [];

        if ($ctr < 1.0) {
            $findings[] = "CTR {$ctr}% is below the 1% benchmark";
            $recommendations[] = 'A/B test ad creative and headline variations to improve click-through rate';
        }
        if ($convRate < 2.0) {
            $findings[] = "Conversion rate {$convRate}% is below the 2% benchmark";
            $recommendations[] = 'Review landing page experience and reduce friction in the conversion funnel';
        }
        if ($roas < 2.0) {
            $findings[] = "ROAS {$roas}x is below the 2x target";
            $recommendations[] = 'Reallocate budget toward highest-converting audience segments and channels';
        }

        return SkillResult::completed(
            [
                'campaign_name' => $campaign['name'] ?? 'unnamed',
                'kpis' => [
                    'impressions' => $impressions,
                    'clicks' => $clicks,
                    'conversions' => $conversions,
                    'ctr_pct' => $ctr,
                    'conversion_rate_pct' => $convRate,
                    'cpc_usd' => $cpc,
                    'cpa_usd' => $cpa,
                    'roas' => $roas,
                    'spend_usd' => $spend,
                    'revenue_usd' => $revenue,
                ],
                'scores' => [
                    'ctr_score' => round($ctrScore, 1),
                    'conversion_score' => round($convScore, 1),
                    'roas_score' => round($roasScore, 1),
                    'overall_health' => $healthScore,
                ],
                'grade' => $this->grade($healthScore),
            ],
            85.0,
            $findings,
            $recommendations
        );
    }

    private function generateContentBrief(array $input): SkillResult
    {
        $goal = $input['goal'] ?? '';
        $audience = $input['audience'] ?? 'general';
        $channel = $input['channel'] ?? 'blog';
        $tone = $input['tone'] ?? 'professional';
        $keywords = $input['keywords'] ?? [];

        if (empty($goal)) {
            return SkillResult::failed('A marketing goal is required for content_brief');
        }

        $brief = [
            'title' => "Content Brief: {$goal}",
            'goal' => $goal,
            'target_audience' => $audience,
            'channel' => $channel,
            'tone_of_voice' => $tone,
            'primary_keywords' => $keywords,
            'structure' => $this->buildContentStructure($channel),
            'cta' => $this->deriveCta($goal),
            'success_metrics' => $this->successMetrics($channel),
            'estimated_word_count' => $this->wordCountForChannel($channel),
        ];

        return SkillResult::completed(
            ['brief' => $brief],
            88.0,
            [],
            ['Review the brief with your content team before production', 'Add brand voice guidelines to the config for more tailored output']
        );
    }
}
