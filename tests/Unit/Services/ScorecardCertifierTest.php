<?php

namespace Tests\Unit\Services;

use App\Services\Governance\Scorecard\ScorecardCertifier;
use Tests\TestCase;

/**
 * Unit tests for ScorecardCertifier.
 *
 * Purely computational — no I/O — so tests run without database.
 */
class ScorecardCertifierTest extends TestCase
{
    private ScorecardCertifier $certifier;

    private array $allPassGates;

    private array $failedGates;

    protected function setUp(): void
    {
        parent::setUp();

        $this->certifier = new ScorecardCertifier;

        $this->allPassGates = ['all_pass' => true];
        $this->failedGates = ['all_pass' => false];
    }

    public function test_gate_failure_overrides_to_level_1_regardless_of_score(): void
    {
        $result = $this->certifier->certify(99, $this->failedGates);

        $this->assertSame(1, $result['level']);
        $this->assertStringContainsString('GATE FAILURE', $result['label']);
    }

    public function test_score_100_returns_level_5_self_governing(): void
    {
        $result = $this->certifier->certify(100, $this->allPassGates);

        $this->assertSame(5, $result['level']);
        $this->assertStringContainsString('SELF-GOVERNING', $result['label']);
    }

    public function test_score_98_to_99_returns_autonomous_enterprise_ready(): void
    {
        foreach ([98, 99] as $score) {
            $result = $this->certifier->certify($score, $this->allPassGates);

            $this->assertSame(5, $result['level'], "Score {$score} should be level 5");
            $this->assertStringContainsString('AUTONOMOUS', $result['label']);
        }
    }

    public function test_score_95_to_97_returns_enterprise_production_ready(): void
    {
        foreach ([95, 96, 97] as $score) {
            $result = $this->certifier->certify($score, $this->allPassGates);

            $this->assertSame(5, $result['level'], "Score {$score} should be level 5");
            $this->assertStringContainsString('PRODUCTION READY', $result['label']);
        }
    }

    public function test_score_90_to_94_returns_enterprise_ready_level_4(): void
    {
        foreach ([90, 92, 94] as $score) {
            $result = $this->certifier->certify($score, $this->allPassGates);

            $this->assertSame(4, $result['level'], "Score {$score} should be level 4");
            $this->assertStringContainsString('ENTERPRISE READY', $result['label']);
        }
    }

    public function test_score_80_to_89_returns_conditional_pass_level_3(): void
    {
        foreach ([80, 85, 89] as $score) {
            $result = $this->certifier->certify($score, $this->allPassGates);

            $this->assertSame(3, $result['level'], "Score {$score} should be level 3");
            $this->assertStringContainsString('CONDITIONAL', $result['label']);
        }
    }

    public function test_score_below_80_returns_high_risk_level_2(): void
    {
        foreach ([79, 50, 0] as $score) {
            $result = $this->certifier->certify($score, $this->allPassGates);

            $this->assertSame(2, $result['level'], "Score {$score} should be level 2");
            $this->assertStringContainsString('HIGH RISK', $result['label']);
        }
    }

    public function test_result_always_contains_label_and_level_keys(): void
    {
        $result = $this->certifier->certify(88, $this->allPassGates);

        $this->assertArrayHasKey('label', $result);
        $this->assertArrayHasKey('level', $result);
        $this->assertIsString($result['label']);
        $this->assertIsInt($result['level']);
    }
}
