<?php

namespace App\Services\Governance\Financial;

/**
 * FinancialDimensionScorer
 *
 * Computes the four scoring dimensions for the Financial Intelligence Score:
 *  - ROI Achievement  (30 pts)
 *  - Cost Efficiency  (25 pts)
 *  - Revenue Impact   (25 pts)
 *  - Cost Trend       (20 pts)
 *
 * Also builds actionable recommendations from dimension results.
 *
 * This class is purely computational — no I/O, no cache, no side-effects.
 * Extracted from FinancialIntelligenceService.
 */
class FinancialDimensionScorer
{
    /**
     * Score ROI Achievement (30 pts).
     * Measures estimated_savings vs total_cost.
     */
    public function scoreROI(float $totalCost, float $totalSavings, array &$dimensions): float
    {
        if ($totalCost <= 0) {
            $dimensions['roi_achievement'] = [
                'score' => 20,
                'roi_pct' => null,
                'status' => 'no_data',
                'note' => 'No cost data recorded yet — baseline awarded',
            ];

            return 20;
        }

        $roi = (($totalSavings - $totalCost) / $totalCost) * 100;

        $points = match (true) {
            $roi >= 200 => 30,
            $roi >= 100 => 26,
            $roi >= 50 => 22,
            $roi >= 0 => 18,
            $roi >= -20 => 10,
            default => 0,
        };

        $dimensions['roi_achievement'] = [
            'score' => $points,
            'roi_pct' => round($roi, 2),
            'total_cost' => $totalCost,
            'total_savings' => $totalSavings,
            'status' => $roi >= 50 ? 'excellent' : ($roi >= 0 ? 'adequate' : 'poor'),
        ];

        return $points;
    }

    /**
     * Score Cost Efficiency (25 pts).
     * Measures cost per completed task vs industry benchmarks.
     */
    public function scoreCostEfficiency(float $totalCost, int $completedTasks, array &$dimensions): float
    {
        if ($completedTasks === 0 || $totalCost <= 0) {
            $dimensions['cost_efficiency'] = [
                'score' => 20,
                'cost_per_task' => null,
                'status' => 'no_data',
                'note' => 'No task cost data yet — baseline awarded',
            ];

            return 20;
        }

        $costPerTask = $totalCost / $completedTasks;

        $points = match (true) {
            $costPerTask < 0.05 => 25,
            $costPerTask < 0.10 => 22,
            $costPerTask < 0.50 => 18,
            $costPerTask < 1.00 => 12,
            $costPerTask < 5.00 => 6,
            default => 0,
        };

        $dimensions['cost_efficiency'] = [
            'score' => $points,
            'cost_per_task' => round($costPerTask, 4),
            'completed_tasks' => $completedTasks,
            'status' => $points >= 18 ? 'excellent' : ($points >= 12 ? 'adequate' : 'poor'),
        ];

        return $points;
    }

    /**
     * Score Revenue Impact (25 pts).
     * Measures revenue multiplier — revenue impact vs cost invested.
     */
    public function scoreRevenueImpact(float $totalRevenue, float $totalCost, array &$dimensions): float
    {
        if ($totalRevenue <= 0 && $totalCost <= 0) {
            $dimensions['revenue_impact'] = [
                'score' => 20,
                'revenue_multiplier' => null,
                'status' => 'no_data',
                'note' => 'No revenue impact data yet — baseline awarded',
            ];

            return 20;
        }

        $multiplier = $totalCost > 0 ? $totalRevenue / $totalCost : 0;

        $points = match (true) {
            $multiplier >= 10 => 25,
            $multiplier >= 5 => 22,
            $multiplier >= 2 => 18,
            $multiplier >= 1 => 14,
            $multiplier > 0 => 8,
            default => 5,
        };

        $dimensions['revenue_impact'] = [
            'score' => $points,
            'total_revenue_impact' => round($totalRevenue, 2),
            'revenue_multiplier' => round($multiplier, 2),
            'status' => $points >= 18 ? 'excellent' : ($points >= 14 ? 'adequate' : 'poor'),
        ];

        return $points;
    }

    /**
     * Score Cost Trend (20 pts).
     * Measures month-over-month cost direction — declining is better.
     */
    public function scoreCostTrend(array $monthlyTrend, array &$dimensions): float
    {
        if (count($monthlyTrend) < 2) {
            $dimensions['cost_trend'] = [
                'score' => 15,
                'direction' => 'insufficient_data',
                'note' => 'Need at least 2 months of data for trend analysis',
            ];

            return 15;
        }

        $costs = array_column($monthlyTrend, 'cost');
        $first = $costs[0];
        $last = $costs[count($costs) - 1];
        $trendPct = $first > 0 ? (($last - $first) / $first) * 100 : 0;

        $points = match (true) {
            $trendPct <= -20 => 20,
            $trendPct <= -5 => 17,
            $trendPct <= 5 => 14,
            $trendPct <= 20 => 10,
            default => 5,
        };

        $dimensions['cost_trend'] = [
            'score' => $points,
            'trend_pct' => round($trendPct, 2),
            'direction' => $trendPct <= -5 ? 'improving' : ($trendPct <= 5 ? 'stable' : 'increasing'),
            'monthly_data' => $monthlyTrend,
        ];

        return $points;
    }

    /**
     * Build actionable financial recommendations from dimension results.
     */
    public function buildRecommendations(array $dimensions, float $totalCost, float $totalSavings, int $scorecardCount): array
    {
        $recs = [];

        if ($scorecardCount === 0) {
            $recs[] = 'Generate agent scorecards to enable financial intelligence tracking';
        }

        if (isset($dimensions['roi_achievement']['roi_pct']) && $dimensions['roi_achievement']['roi_pct'] < 50) {
            $recs[] = 'ROI below 50% — review agent deployment configurations and task automation coverage';
        }

        if (isset($dimensions['cost_efficiency']['cost_per_task']) && $dimensions['cost_efficiency']['cost_per_task'] > 0.50) {
            $recs[] = 'Cost per task exceeds $0.50 — optimize token budgets and tool call limits';
        }

        if (isset($dimensions['cost_trend']['direction']) && $dimensions['cost_trend']['direction'] === 'increasing') {
            $recs[] = 'Cost trend is increasing — review agent token budgets and reduce MAX_TOKENS_PER_TASK';
        }

        return $recs;
    }
}
