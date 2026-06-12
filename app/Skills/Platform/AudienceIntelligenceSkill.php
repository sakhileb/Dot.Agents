<?php

namespace App\Skills\Platform;

use App\Skills\DTOs\SkillResult;

/**
 * AudienceIntelligenceSkill — audience segmentation and ROI measurement (Layer 5 — Platform Intelligence)
 *
 * Handles the two audience-focused marketing actions extracted from MarketingIntelligenceSkill:
 *   segment_audience – cluster an audience by industry and company size
 *   measure_roi      – calculate marketing ROI and ROAS from spend/revenue inputs
 *
 * Shares messaging angle helpers with CampaignIntelligenceSkill via MarketingHelper.
 */
class AudienceIntelligenceSkill extends MarketingHelper
{
    public function key(): string
    {
        return 'audience-intelligence';
    }

    public function layer(): string
    {
        return 'platform';
    }

    public function execute(array $input, array $context = []): SkillResult
    {
        $action = $input['action'] ?? 'segment_audience';

        return match ($action) {
            'segment_audience' => $this->segmentAudience($input),
            'measure_roi' => $this->measureRoi($input),
            default => SkillResult::failed("Unknown audience-intelligence action: [{$action}]"),
        };
    }

    private function segmentAudience(array $input): SkillResult
    {
        $audienceData = $input['audience'] ?? [];

        if (empty($audienceData)) {
            return SkillResult::failed('Audience attribute data is required for segmentation');
        }

        $rows = is_array($audienceData[0] ?? null) ? $audienceData : [$audienceData];

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

        foreach ($segments as &$seg) {
            $scores = array_filter(array_column($seg['members'], 'score'));
            $seg['avg_score'] = count($scores) > 0 ? round(array_sum($scores) / count($scores), 1) : null;
            unset($seg['members']);
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
}
