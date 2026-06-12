<?php

namespace Tests\Unit\Services;

use App\Services\AI\EnterpriseBrainScorer;
use Tests\TestCase;

/**
 * Unit tests for EnterpriseBrainScorer.
 * All methods are purely computational — no I/O — so no database needed.
 */
class EnterpriseBrainScorerTest extends TestCase
{
    private EnterpriseBrainScorer $scorer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scorer = new EnterpriseBrainScorer;
    }

    // ── computeEconomicScore ──────────────────────────────────────────────────

    public function test_economic_score_returns_95_for_roi_above_300(): void
    {
        $this->assertSame(95, $this->scorer->computeEconomicScore(301));
        $this->assertSame(95, $this->scorer->computeEconomicScore(500));
    }

    public function test_economic_score_returns_85_for_roi_201_to_300(): void
    {
        $this->assertSame(85, $this->scorer->computeEconomicScore(201));
        $this->assertSame(85, $this->scorer->computeEconomicScore(300));
    }

    public function test_economic_score_returns_75_for_roi_101_to_200(): void
    {
        $this->assertSame(75, $this->scorer->computeEconomicScore(101));
        $this->assertSame(75, $this->scorer->computeEconomicScore(200));
    }

    public function test_economic_score_returns_40_for_zero_roi(): void
    {
        $this->assertSame(40, $this->scorer->computeEconomicScore(0));
        $this->assertSame(40, $this->scorer->computeEconomicScore(-100));
    }

    public function test_economic_score_boundaries(): void
    {
        $this->assertSame(65, $this->scorer->computeEconomicScore(51));  // > 50
        $this->assertSame(55, $this->scorer->computeEconomicScore(1));   // > 0
    }

    // ── computeEconomicStatus ─────────────────────────────────────────────────

    public function test_economic_status_healthy_at_75_or_above(): void
    {
        $this->assertSame('healthy', $this->scorer->computeEconomicStatus(75));
        $this->assertSame('healthy', $this->scorer->computeEconomicStatus(100));
    }

    public function test_economic_status_monitor_between_55_and_74(): void
    {
        $this->assertSame('monitor', $this->scorer->computeEconomicStatus(55));
        $this->assertSame('monitor', $this->scorer->computeEconomicStatus(74));
    }

    public function test_economic_status_critical_below_55(): void
    {
        $this->assertSame('critical', $this->scorer->computeEconomicStatus(54));
        $this->assertSame('critical', $this->scorer->computeEconomicStatus(0));
    }

    // ── computeGovernanceScore ────────────────────────────────────────────────

    public function test_governance_score_100_when_no_pending_approvals(): void
    {
        $this->assertSame(100, $this->scorer->computeGovernanceScore(0, 0));
    }

    public function test_governance_score_deducts_2_per_pending_approval(): void
    {
        // 5 pending, 0 overdue → 100 - 10 = 90
        $this->assertSame(90, $this->scorer->computeGovernanceScore(5, 0));
    }

    public function test_governance_score_deducts_5_per_overdue_approval(): void
    {
        // 0 pending, 4 overdue → 100 - 20 = 80
        $this->assertSame(80, $this->scorer->computeGovernanceScore(0, 4));
    }

    public function test_governance_score_clamps_to_zero(): void
    {
        // 100 pending would give -100, clamped to 0
        $this->assertSame(0, $this->scorer->computeGovernanceScore(100, 0));
    }

    public function test_governance_score_clamps_to_100(): void
    {
        $this->assertSame(100, $this->scorer->computeGovernanceScore(0, 0));
    }

    // ── computeGovernanceStatus ───────────────────────────────────────────────

    public function test_governance_status_compliant_at_80_or_above(): void
    {
        $this->assertSame('compliant', $this->scorer->computeGovernanceStatus(80));
        $this->assertSame('compliant', $this->scorer->computeGovernanceStatus(100));
    }

    public function test_governance_status_review_needed_between_60_and_79(): void
    {
        $this->assertSame('review_needed', $this->scorer->computeGovernanceStatus(60));
        $this->assertSame('review_needed', $this->scorer->computeGovernanceStatus(79));
    }

    public function test_governance_status_non_compliant_below_60(): void
    {
        $this->assertSame('non_compliant', $this->scorer->computeGovernanceStatus(59));
        $this->assertSame('non_compliant', $this->scorer->computeGovernanceStatus(0));
    }

    // ── buildDomainScores ─────────────────────────────────────────────────────

    public function test_build_domain_scores_returns_8_domains(): void
    {
        $scores = $this->scorer->buildDomainScores(85, 90.0, 0, 95);

        $this->assertCount(8, $scores);
    }

    public function test_build_domain_scores_expected_keys(): void
    {
        $scores = $this->scorer->buildDomainScores(85, 90.0, 2, 80);

        $expectedKeys = [
            'revenue_health', 'customer_health', 'security_health',
            'agent_health', 'workflow_health', 'compliance_health',
            'operational_health', 'technology_health',
        ];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $scores, "Missing domain key: {$key}");
        }
    }

    public function test_build_domain_scores_workflow_reduces_with_bottlenecks(): void
    {
        $none = $this->scorer->buildDomainScores(85, 90.0, 0, 80);
        $some = $this->scorer->buildDomainScores(85, 90.0, 5, 80);

        // 5 bottlenecks × 5 = 25 point deduction
        $this->assertSame(100.0, $none['workflow_health']);
        $this->assertSame(75.0, $some['workflow_health']);
    }

    public function test_build_domain_scores_workflow_clamps_to_zero(): void
    {
        $scores = $this->scorer->buildDomainScores(85, 90.0, 30, 80);

        $this->assertSame(0.0, $scores['workflow_health']);
        $this->assertSame(0.0, $scores['operational_health']);
    }

    // ── computeCompositeScore ─────────────────────────────────────────────────

    public function test_composite_score_is_average_of_domain_scores(): void
    {
        $domains = [
            'a' => 80.0,
            'b' => 90.0,
            'c' => 70.0,
            'd' => 100.0,
        ];

        // (80 + 90 + 70 + 100) / 4 = 85.0
        $this->assertSame(85.0, $this->scorer->computeCompositeScore($domains));
    }

    public function test_composite_score_returns_zero_for_empty_array(): void
    {
        $this->assertSame(0.0, $this->scorer->computeCompositeScore([]));
    }
}
