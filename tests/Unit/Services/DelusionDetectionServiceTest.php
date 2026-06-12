<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\Governance\DelusionDetectionService;
use Tests\TestCase;

class DelusionDetectionServiceTest extends TestCase
{
    private DelusionDetectionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DelusionDetectionService;
    }

    public function test_returns_required_keys(): void
    {
        $result = $this->service->analyze('Summarize tasks', ['summary' => 'Done.'], []);

        $this->assertArrayHasKey('risk_score', $result);
        $this->assertArrayHasKey('reality_alignment', $result);
        $this->assertArrayHasKey('verification_score', $result);
        $this->assertArrayHasKey('evidence_quality', $result);
        $this->assertArrayHasKey('source_credibility', $result);
        $this->assertArrayHasKey('assumption_count', $result);
        $this->assertArrayHasKey('flags', $result);
    }

    public function test_high_confidence_with_evidence_gives_low_risk(): void
    {
        $result = $this->service->analyze(
            'Analyze this revenue data',
            [
                'summary' => 'Revenue is $1.2M.',
                'confidence' => 90,
                'evidence' => [['source' => 'revenue_data', 'value' => 1200000]],
                'assumptions' => [],
            ],
            ['revenue' => 1200000]
        );

        $this->assertLessThan(60, $result['risk_score']);
    }

    public function test_many_assumptions_increases_risk_score(): void
    {
        $result = $this->service->analyze(
            'Forecast next quarter',
            [
                'summary' => 'Revenue will grow.',
                'confidence' => 95,
                'assumptions' => ['a1', 'a2', 'a3', 'a4', 'a5'],
            ],
            []
        );

        $this->assertGreaterThan(0, $result['assumption_count']);
    }

    public function test_risk_score_is_bounded_zero_to_hundred(): void
    {
        $result = $this->service->analyze('', ['confidence' => 0, 'assumptions' => []], []);

        $this->assertGreaterThanOrEqual(0, $result['risk_score']);
        $this->assertLessThanOrEqual(100, $result['risk_score']);
    }

    public function test_flags_is_array(): void
    {
        $result = $this->service->analyze('test', [], []);
        $this->assertIsArray($result['flags']);
    }
}
