<?php

namespace Tests\Unit\Services;

use App\Services\Governance\DelusionDetectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DelusionDetectionServiceFullTest extends TestCase
{
    use RefreshDatabase;

    private DelusionDetectionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(DelusionDetectionService::class);
    }

    public function test_returns_low_risk_score_for_factual_grounded_output(): void
    {
        $task = 'Summarize the quarterly sales report attached.';
        $output = [
            'summary' => 'Q3 sales were $2.4M, up 12% from Q2 based on the attached report data.',
            'confidence' => 92.0,
            'evidence' => ['Attached Q3 sales report', 'Historical Q2 data'],
            'reasoning' => 'Direct data extraction from provided documents.',
        ];
        $context = ['documents' => ['Q3 Sales Report'], 'data_quality' => 'high'];

        $result = $this->service->analyze($task, $output, $context);

        $this->assertArrayHasKey('risk_score', $result);
        $this->assertLessThan(45, $result['risk_score']);
    }

    public function test_returns_high_risk_score_for_fabricated_uncertain_output(): void
    {
        $task = 'What will the stock price be tomorrow?';
        $output = [
            'summary' => 'The stock will definitely be $542.00 tomorrow based on my prediction.',
            'confidence' => 98.0,
            'evidence' => [],
            'assumptions' => ['Markets will behave as I predict', 'No external events'],
        ];
        $context = [];

        $result = $this->service->analyze($task, $output, $context);

        $this->assertGreaterThan(60, $result['risk_score']);
    }

    public function test_detects_overconfidence_as_risk_factor(): void
    {
        $output = [
            'summary' => 'The answer is absolutely certain.',
            'confidence' => 99.9,
            'evidence' => [],
        ];

        $result = $this->service->analyze('Any task', $output, []);

        $this->assertGreaterThan(40, $result['risk_score']);
    }

    public function test_result_includes_all_required_scoring_fields(): void
    {
        $result = $this->service->analyze('test task', ['summary' => 'test'], []);

        $this->assertArrayHasKey('risk_score', $result);
        $this->assertArrayHasKey('reality_alignment', $result);
        $this->assertArrayHasKey('verification_score', $result);
        $this->assertArrayHasKey('evidence_quality_score', $result);
    }

    public function test_risk_score_is_within_valid_range(): void
    {
        $result = $this->service->analyze('test', ['summary' => 'test output'], []);

        $this->assertGreaterThanOrEqual(0, $result['risk_score']);
        $this->assertLessThanOrEqual(100, $result['risk_score']);
    }
}
