<?php

namespace App\Skills\Platform;

use App\Skills\BaseSkill;
use App\Skills\DTOs\SkillResult;

/**
 * Marketing Intelligence Skill (Layer 5 — Platform Intelligence)
 *
 * Equips agents with professional marketing-grade analysis capabilities.
 * Inspired by coreyhaines31/marketingskills — brings campaign analysis,
 * content ideation, audience segmentation, and ROI measurement into the
 * agent skill set.
 *
 * Actions:
 *   analyze_campaign     – scorecard a campaign's key performance indicators
 *   content_brief        – generate a structured content brief from a goal
 *   segment_audience     – profile and segment an audience from attribute data
 *   measure_roi          – calculate marketing ROI across spend/revenue inputs
 */
class MarketingIntelligenceSkill extends BaseSkill
{
    public function key(): string
    {
        return 'marketing-intelligence';
    }

    public function layer(): string
    {
        return 'platform';
    }

    /**
     * Input keys:
     *   action       – analyze_campaign | content_brief | segment_audience | measure_roi
     *   campaign     – array of campaign metrics (for analyze_campaign / measure_roi)
     *   goal         – string marketing objective (for content_brief)
     *   audience     – array of audience attribute objects (for segment_audience)
     *   spend        – float total spend (for measure_roi)
     *   revenue      – float total attributed revenue (for measure_roi)
     */
    public function execute(array $input, array $context = []): SkillResult
    {
        $action = $input['action'] ?? 'analyze_campaign';

        return match ($action) {
            'analyze_campaign' => $this->analyzeCampaign($input),
            'content_brief' => $this->generateContentBrief($input),
            'segment_audience' => $this->segmentAudience($input),
            'measure_roi' => $this->measureRoi($input),
            default => SkillResult::failed("Unknown marketing-intelligence action: [{$action}]"),
        };
    }

    // ── Actions ──────────────────────────────────────────

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

        // Composite health score
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

    private function segmentAudience(array $input): SkillResult
    {
        $audienceData = $input['audience'] ?? [];

        if (empty($audienceData)) {
            return SkillResult::failed('Audience attribute data is required for segmentation');
        }

        $rows = is_array($audienceData[0] ?? null) ? $audienceData : [$audienceData];

        // Cluster by industry/role if present, otherwise by size/maturity proxy
        $segments = [];

        foreach ($rows as $row) {
            $industry = $row['industry'] ?? $row['sector'] ?? 'unknown';
            $size = $row['company_size'] ?? $row['size'] ?? 'unknown';
            $key = "{$industry}_{$size}";

            if (! isset($segments[$key])) {
                $segments[$key] = [
                    'segment_id' => $key,
                    'industry' => $industry,
                    'company_size' => $size,
                    'members' => [],
                    'count' => 0,
                    'avg_score' => 0,
                    'messaging_angle' => $this->messagingAngle($industry),
                ];
            }

            $segments[$key]['members'][] = $row;
            $segments[$key]['count']++;
        }

        // Compute avg_score per segment
        foreach ($segments as &$seg) {
            $scores = array_filter(array_column($seg['members'], 'score'));
            $seg['avg_score'] = count($scores) > 0 ? round(array_sum($scores) / count($scores), 1) : null;
            unset($seg['members']); // Keep output lean
        }

        $segments = array_values($segments);
        usort($segments, fn ($a, $b) => $b['count'] <=> $a['count']);

        return SkillResult::completed(
            [
                'total_audience' => count($rows),
                'segments' => $segments,
                'segment_count' => count($segments),
                'top_segment' => $segments[0] ?? null,
            ],
            82.0
        );
    }

    private function measureRoi(array $input): SkillResult
    {
        $spend = (float) ($input['spend'] ?? ($input['campaign']['spend'] ?? 0));
        $revenue = (float) ($input['revenue'] ?? ($input['campaign']['revenue'] ?? 0));
        $operationalCost = (float) ($input['operational_cost'] ?? 0);

        if ($spend <= 0) {
            return SkillResult::failed('Marketing spend must be greater than 0 to calculate ROI');
        }

        $totalInvestment = $spend + $operationalCost;
        $netProfit = $revenue - $totalInvestment;
        $roi = round(($netProfit / $totalInvestment) * 100, 2);
        $roas = round($revenue / $spend, 2);

        $findings = [];
        $recommendations = [];

        if ($roi < 0) {
            $findings[] = "Negative ROI {$roi}% — campaign is losing money";
            $recommendations[] = 'Pause underperforming ad sets and reallocate budget to high-ROAS channels';
        } elseif ($roi < 50) {
            $recommendations[] = 'ROI below 50% — investigate cost-reduction opportunities and conversion optimisation';
        }

        return SkillResult::completed(
            [
                'spend_usd' => $spend,
                'operational_cost_usd' => $operationalCost,
                'total_investment_usd' => $totalInvestment,
                'attributed_revenue_usd' => $revenue,
                'net_profit_usd' => round($netProfit, 2),
                'roi_pct' => $roi,
                'roas' => $roas,
                'status' => $roi >= 100 ? 'excellent' : ($roi >= 50 ? 'good' : ($roi >= 0 ? 'break_even' : 'loss')),
            ],
            92.0,
            $findings,
            $recommendations
        );
    }

    // ── Helpers ──────────────────────────────────────────

    private function buildContentStructure(string $channel): array
    {
        return match ($channel) {
            'blog' => ['Hook / opening paragraph', 'Problem statement', 'Solution overview', '3-5 key points with evidence', 'Case study or example', 'CTA with value proposition'],
            'email' => ['Subject line (A/B variants)', 'Preheader text', 'Personalised greeting', 'Core value proposition (2-3 sentences)', 'Bullet benefits', 'Single strong CTA button'],
            'social' => ['Attention-grabbing first line', 'Core message (1-2 sentences)', 'Supporting visual description', 'Hashtag strategy', 'CTA or question to drive engagement'],
            'video' => ['Hook (0–5 sec)', 'Problem agitation (5–15 sec)', 'Solution reveal (15–30 sec)', 'Proof/social validation (30–45 sec)', 'CTA (final 10 sec)'],
            default => ['Introduction', 'Main content', 'Conclusion', 'CTA'],
        };
    }

    private function deriveCta(string $goal): string
    {
        $lower = strtolower($goal);
        if (str_contains($lower, 'lead') || str_contains($lower, 'signup')) {
            return 'Start your free trial today';
        }
        if (str_contains($lower, 'sale') || str_contains($lower, 'revenue')) {
            return 'Get started — no credit card required';
        }
        if (str_contains($lower, 'brand') || str_contains($lower, 'awareness')) {
            return 'Learn how 500+ companies transformed their operations';
        }

        return 'Request a personalised demo';
    }

    private function successMetrics(string $channel): array
    {
        return match ($channel) {
            'blog' => ['organic_traffic', 'time_on_page', 'lead_conversions', 'backlinks'],
            'email' => ['open_rate', 'click_through_rate', 'conversion_rate', 'unsubscribe_rate'],
            'social' => ['reach', 'engagement_rate', 'shares', 'profile_visits', 'link_clicks'],
            'video' => ['view_count', 'watch_time', 'click_through_rate', 'conversion_rate'],
            default => ['reach', 'engagement', 'conversions'],
        };
    }

    private function wordCountForChannel(string $channel): string
    {
        return match ($channel) {
            'blog' => '1,000–2,500 words',
            'email' => '150–300 words',
            'social' => '50–280 characters',
            'video' => '150–300 word script (~60–90 sec)',
            default => '500–1,000 words',
        };
    }

    private function messagingAngle(string $industry): string
    {
        return match (strtolower($industry)) {
            'finance', 'banking' => 'Compliance-first efficiency and risk reduction',
            'healthcare', 'health' => 'Patient outcomes and regulatory confidence',
            'technology', 'tech', 'software' => 'Velocity, scalability, and developer experience',
            'retail', 'ecommerce' => 'Conversion uplift and seamless customer journeys',
            'manufacturing' => 'Operational efficiency and supply-chain resilience',
            'education' => 'Learning outcomes and accessibility at scale',
            default => 'Operational excellence and measurable ROI',
        };
    }
}
