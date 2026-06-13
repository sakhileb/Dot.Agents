<?php

namespace Tests\Unit\Services;

use App\Services\Governance\Scorecard\ScorecardGateEvaluator;
use Tests\TestCase;

/**
 * Unit tests for ScorecardGateEvaluator.
 *
 * This class is purely computational — no I/O — so tests run without database.
 */
class ScorecardGateEvaluatorTest extends TestCase
{
    private ScorecardGateEvaluator $evaluator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->evaluator = new ScorecardGateEvaluator;
    }

    private function passingInputs(): array
    {
        return [
            'dataTrust' => ['score' => 95],
            'agentReliability' => ['score' => 92],
            'predictionAcc' => [
                'score' => 92,
                'total_decisions' => 10,
                'dimensions' => ['realityAlign' => ['avg_alignment' => 90]],
            ],
            'observability' => ['score' => 70],
            'disResult' => ['quarantined' => 0, 'critical' => 0, 'total_agents' => 5, 'healthy' => 5],
        ];
    }

    public function test_returns_all_required_gate_keys(): void
    {
        $p = $this->passingInputs();

        $result = $this->evaluator->evaluate(
            90, $p['dataTrust'], $p['agentReliability'],
            $p['predictionAcc'], $p['observability'], $p['disResult']
        );

        $this->assertArrayHasKey('all_pass', $result);
        $this->assertArrayHasKey('no_critical_security_findings', $result);
        $this->assertArrayHasKey('data_trust_ge_90', $result);
        $this->assertArrayHasKey('agent_reliability_ge_90', $result);
        $this->assertArrayHasKey('ai_reliability_ge_90', $result);
        $this->assertArrayHasKey('reality_alignment_ge_85', $result);
        $this->assertArrayHasKey('observability_ge_85', $result);
    }

    public function test_all_pass_true_when_all_thresholds_met(): void
    {
        $p = $this->passingInputs();

        $result = $this->evaluator->evaluate(
            90, $p['dataTrust'], $p['agentReliability'],
            $p['predictionAcc'], $p['observability'], $p['disResult']
        );

        $this->assertTrue($result['all_pass']);
    }

    public function test_critical_security_findings_gate_fails_when_quarantined(): void
    {
        $p = $this->passingInputs();
        $p['disResult']['quarantined'] = 2;

        $result = $this->evaluator->evaluate(
            90, $p['dataTrust'], $p['agentReliability'],
            $p['predictionAcc'], $p['observability'], $p['disResult']
        );

        $this->assertFalse($result['no_critical_security_findings']['pass']);
        $this->assertFalse($result['all_pass']);
    }

    public function test_data_trust_gate_fails_below_90(): void
    {
        $p = $this->passingInputs();
        $p['dataTrust']['score'] = 89;

        $result = $this->evaluator->evaluate(
            90, $p['dataTrust'], $p['agentReliability'],
            $p['predictionAcc'], $p['observability'], $p['disResult']
        );

        $this->assertFalse($result['data_trust_ge_90']['pass']);
        $this->assertFalse($result['all_pass']);
    }

    public function test_ai_reliability_gate_passes_when_no_decision_data(): void
    {
        $p = $this->passingInputs();
        // Zero decisions → grace pass regardless of score
        $p['predictionAcc']['total_decisions'] = 0;
        $p['predictionAcc']['score'] = 50; // Would fail if data existed

        $result = $this->evaluator->evaluate(
            90, $p['dataTrust'], $p['agentReliability'],
            $p['predictionAcc'], $p['observability'], $p['disResult']
        );

        $this->assertTrue($result['ai_reliability_ge_90']['pass']);
        $this->assertStringContainsString('grace', $result['ai_reliability_ge_90']['note'] ?? '');
    }

    public function test_agent_reliability_gate_fails_below_90(): void
    {
        $p = $this->passingInputs();
        $p['agentReliability']['score'] = 89;

        $result = $this->evaluator->evaluate(
            90, $p['dataTrust'], $p['agentReliability'],
            $p['predictionAcc'], $p['observability'], $p['disResult']
        );

        $this->assertFalse($result['agent_reliability_ge_90']['pass']);
        $this->assertFalse($result['all_pass']);
    }

    public function test_each_gate_includes_value_and_threshold_keys(): void
    {
        $p = $this->passingInputs();

        $result = $this->evaluator->evaluate(
            90, $p['dataTrust'], $p['agentReliability'],
            $p['predictionAcc'], $p['observability'], $p['disResult']
        );

        $gateKeys = [
            'no_critical_security_findings', 'data_trust_ge_90',
            'agent_reliability_ge_90', 'ai_reliability_ge_90',
            'reality_alignment_ge_85', 'observability_ge_85',
        ];

        foreach ($gateKeys as $key) {
            $this->assertArrayHasKey('pass', $result[$key], "{$key} missing 'pass'");
            $this->assertArrayHasKey('value', $result[$key], "{$key} missing 'value'");
            $this->assertArrayHasKey('threshold', $result[$key], "{$key} missing 'threshold'");
        }
    }
}
