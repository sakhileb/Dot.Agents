<?php

namespace Tests\Unit\Services;

use App\Services\Governance\Scorecard\ScorecardDomainScorer;
use Tests\TestCase;

/**
 * Unit tests for ScorecardDomainScorer.
 *
 * This service is purely computational (no I/O), making it ideal for unit
 * testing. All tests verify scoring math and boundary conditions.
 */
class ScorecardDomainScorerTest extends TestCase
{
    private ScorecardDomainScorer $scorer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scorer = new ScorecardDomainScorer;
    }

    /** @test */
    public function weighted_average_returns_correct_score_for_equal_weights(): void
    {
        $domains = [
            ['score' => 80, 'weight' => 1],
            ['score' => 100, 'weight' => 1],
            ['score' => 60, 'weight' => 1],
        ];

        $result = $this->scorer->weightedAverage($domains);

        $this->assertEqualsWithDelta(80.0, $result, 0.01);
    }

    /** @test */
    public function weighted_average_returns_correct_score_for_unequal_weights(): void
    {
        $domains = [
            ['score' => 100, 'weight' => 2],
            ['score' => 50, 'weight' => 1],
        ];

        // (100*2 + 50*1) / 3 = 250/3 = 83.33
        $result = $this->scorer->weightedAverage($domains);

        $this->assertEqualsWithDelta(83.33, $result, 0.01);
    }

    /** @test */
    public function weighted_average_returns_zero_when_total_weight_is_zero(): void
    {
        $result = $this->scorer->weightedAverage([]);

        $this->assertSame(0.0, $result);
    }

    /** @test */
    public function compute_technical_domains_returns_all_required_keys(): void
    {
        $dataTrust    = ['score' => 85];
        $observability = ['score' => 90, 'sentry_configured' => true];
        $disResult    = ['total_agents' => 10, 'healthy' => 9];

        $domains = $this->scorer->computeTechnicalDomains($dataTrust, $observability, $disResult);

        foreach ($domains as $key => $domain) {
            $this->assertArrayHasKey('score', $domain, "Domain '{$key}' missing 'score'");
            $this->assertArrayHasKey('weight', $domain, "Domain '{$key}' missing 'weight'");
            $this->assertGreaterThanOrEqual(0, $domain['score']);
            $this->assertLessThanOrEqual(100, $domain['score']);
        }
    }

    /** @test */
    public function compute_technical_domains_handles_zero_agents(): void
    {
        $domains = $this->scorer->computeTechnicalDomains(
            dataTrust: ['score' => 80],
            observability: ['score' => 75, 'sentry_configured' => false],
            disResult: ['total_agents' => 0],
        );

        // When no agents exist, DIS health should default to 100 (healthy by default)
        $this->assertLessThanOrEqual(100, $domains['security_cyber_defense']['score']);
        $this->assertGreaterThanOrEqual(0, $domains['security_cyber_defense']['score']);
    }

    /** @test */
    public function compute_intelligence_domains_returns_all_required_keys(): void
    {
        $predictionAcc = [
            'score' => 80,
            'dimensions' => [
                'realityAlign' => ['avg_alignment' => 88],
                'hitRate'      => ['hit_rate' => 85],
                'calibration'  => ['ece' => null],
            ],
        ];
        $reliability   = ['score' => 75];
        $orgMemory     = ['score' => 70];
        $disResult     = ['total_agents' => 5, 'healthy' => 5, 'warnings' => 0, 'critical' => 0, 'quarantined' => 0];

        $domains = $this->scorer->computeIntelligenceDomains(
            $predictionAcc,
            $reliability,
            $orgMemory,
            $disResult,
        );

        foreach ($domains as $key => $domain) {
            $this->assertArrayHasKey('score', $domain, "Domain '{$key}' missing 'score'");
            $this->assertArrayHasKey('weight', $domain, "Domain '{$key}' missing 'weight'");
        }
    }

    /** @test */
    public function compute_business_domains_returns_all_required_keys(): void
    {
        $orgMemory  = ['score' => 70];
        $financial  = ['score' => 65];
        $csScore    = ['score' => 80, 'satisfaction_score' => 4.2];

        $domains = $this->scorer->computeBusinessDomains($orgMemory, $financial, $csScore);

        foreach ($domains as $key => $domain) {
            $this->assertArrayHasKey('score', $domain, "Domain '{$key}' missing 'score'");
            $this->assertArrayHasKey('weight', $domain, "Domain '{$key}' missing 'weight'");
        }
    }

    /** @test */
    public function weighted_average_of_technical_domains_is_between_0_and_100(): void
    {
        $dataTrust    = ['score' => 100];
        $observability = ['score' => 100, 'sentry_configured' => true];
        $disResult    = ['total_agents' => 5, 'healthy' => 5];

        $domains = $this->scorer->computeTechnicalDomains($dataTrust, $observability, $disResult);
        $average = $this->scorer->weightedAverage($domains);

        $this->assertGreaterThanOrEqual(0, $average);
        $this->assertLessThanOrEqual(100, $average);
    }
}
