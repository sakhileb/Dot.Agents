<?php

namespace Tests\Unit\Services;

use App\Services\Governance\Financial\FinancialDimensionScorer;
use Tests\TestCase;

/**
 * Unit tests for FinancialDimensionScorer.
 * All methods are purely computational — no I/O — so no database needed.
 */
class FinancialDimensionScorerTest extends TestCase
{
    private FinancialDimensionScorer $scorer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scorer = new FinancialDimensionScorer;
    }

    // ── scoreROI ──────────────────────────────────────────────────────────────

    public function test_score_roi_returns_20_baseline_when_no_cost_data(): void
    {
        $dimensions = [];
        $score = $this->scorer->scoreROI(0, 0, $dimensions);

        $this->assertSame(20.0, $score);
        $this->assertSame('no_data', $dimensions['roi_achievement']['status']);
    }

    public function test_score_roi_returns_30_for_roi_above_200_pct(): void
    {
        // cost = 100, savings = 400 → ROI = (400-100)/100 * 100 = 300%
        $dimensions = [];
        $score = $this->scorer->scoreROI(100, 400, $dimensions);

        $this->assertSame(30.0, $score);
    }

    public function test_score_roi_returns_0_for_deeply_negative_roi(): void
    {
        // cost = 100, savings = 70 → ROI = -30% → default = 0
        $dimensions = [];
        $score = $this->scorer->scoreROI(100, 70, $dimensions);

        $this->assertSame(0.0, $score);
    }

    public function test_score_roi_populates_dimension_with_roi_pct(): void
    {
        // cost = 100, savings = 200 → ROI = (200-100)/100 * 100 = 100%
        $dimensions = [];
        $this->scorer->scoreROI(100, 200, $dimensions);

        $this->assertArrayHasKey('roi_achievement', $dimensions);
        $this->assertSame(100.0, $dimensions['roi_achievement']['roi_pct']);
    }

    // ── scoreCostEfficiency ───────────────────────────────────────────────────

    public function test_score_cost_efficiency_baseline_when_no_tasks(): void
    {
        $dimensions = [];
        $score = $this->scorer->scoreCostEfficiency(100, 0, $dimensions);

        $this->assertSame(20.0, $score);
        $this->assertSame('no_data', $dimensions['cost_efficiency']['status']);
    }

    public function test_score_cost_efficiency_25_for_sub_5_cent_per_task(): void
    {
        // cost = $1, tasks = 100 → $0.01/task → max score
        $dimensions = [];
        $score = $this->scorer->scoreCostEfficiency(1.0, 100, $dimensions);

        $this->assertSame(25.0, $score);
    }

    public function test_score_cost_efficiency_0_for_expensive_tasks(): void
    {
        // cost = $50, tasks = 1 → $50/task → 0 pts
        $dimensions = [];
        $score = $this->scorer->scoreCostEfficiency(50.0, 1, $dimensions);

        $this->assertSame(0.0, $score);
    }

    // ── scoreRevenueImpact ────────────────────────────────────────────────────

    public function test_score_revenue_impact_baseline_when_no_data(): void
    {
        $dimensions = [];
        $score = $this->scorer->scoreRevenueImpact(0, 0, $dimensions);

        $this->assertSame(20.0, $score);
        $this->assertSame('no_data', $dimensions['revenue_impact']['status']);
    }

    public function test_score_revenue_impact_25_for_10x_multiplier(): void
    {
        // revenue = $1000, cost = $100 → 10x multiplier
        $dimensions = [];
        $score = $this->scorer->scoreRevenueImpact(1000.0, 100.0, $dimensions);

        $this->assertSame(25.0, $score);
    }

    public function test_score_revenue_impact_5_when_zero_revenue(): void
    {
        // revenue = 0, cost = $100 → multiplier = 0 → default
        $dimensions = [];
        $score = $this->scorer->scoreRevenueImpact(0.0, 100.0, $dimensions);

        $this->assertSame(5.0, $score);
    }

    // ── scoreCostTrend ────────────────────────────────────────────────────────

    public function test_score_cost_trend_baseline_with_insufficient_data(): void
    {
        $dimensions = [];
        $score = $this->scorer->scoreCostTrend([], $dimensions);

        $this->assertSame(15.0, $score);
        $this->assertSame('insufficient_data', $dimensions['cost_trend']['direction']);
    }

    public function test_score_cost_trend_20_for_declining_costs(): void
    {
        // Month 1: $100, Month 2: $70 → -30% → max score
        $trend = [
            ['month' => '2026-01', 'cost' => 100.0, 'savings' => 0.0, 'records' => 1],
            ['month' => '2026-02', 'cost' => 70.0,  'savings' => 0.0, 'records' => 1],
        ];
        $dimensions = [];
        $score = $this->scorer->scoreCostTrend($trend, $dimensions);

        $this->assertSame(20.0, $score);
        $this->assertSame('improving', $dimensions['cost_trend']['direction']);
    }

    public function test_score_cost_trend_5_for_sharply_increasing_costs(): void
    {
        // Month 1: $100, Month 2: $200 → +100% → minimum score
        $trend = [
            ['month' => '2026-01', 'cost' => 100.0, 'savings' => 0.0, 'records' => 1],
            ['month' => '2026-02', 'cost' => 200.0, 'savings' => 0.0, 'records' => 1],
        ];
        $dimensions = [];
        $score = $this->scorer->scoreCostTrend($trend, $dimensions);

        $this->assertSame(5.0, $score);
        $this->assertSame('increasing', $dimensions['cost_trend']['direction']);
    }

    // ── buildRecommendations ──────────────────────────────────────────────────

    public function test_build_recommendations_empty_when_all_good(): void
    {
        $dimensions = [
            'roi_achievement' => ['roi_pct' => 100.0],
            'cost_efficiency' => ['cost_per_task' => 0.01],
            'cost_trend' => ['direction' => 'improving'],
        ];

        $recs = $this->scorer->buildRecommendations($dimensions, 100, 200, 3);

        $this->assertIsArray($recs);
        $this->assertEmpty($recs);
    }

    public function test_build_recommendations_flags_low_roi(): void
    {
        $dimensions = [
            'roi_achievement' => ['roi_pct' => 20.0],
        ];

        $recs = $this->scorer->buildRecommendations($dimensions, 100, 50, 1);

        $this->assertNotEmpty($recs);
        $this->assertTrue(collect($recs)->contains(fn ($r) => str_contains($r, 'ROI')));
    }

    public function test_build_recommendations_flags_no_scorecard_data(): void
    {
        $recs = $this->scorer->buildRecommendations([], 0, 0, 0);

        $this->assertNotEmpty($recs);
        $this->assertTrue(collect($recs)->contains(fn ($r) => str_contains($r, 'scorecard')));
    }
}
