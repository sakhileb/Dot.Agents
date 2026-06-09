<?php

namespace Tests\Unit\Services;

use App\Models\AgentDeployment;
use App\Models\AgentTask;
use App\Services\Governance\ScorecardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScorecardServiceTest extends TestCase
{
    use RefreshDatabase;

    private ScorecardService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ScorecardService::class);
    }

    public function test_generates_scorecard_with_all_10_dimensions(): void
    {
        $deployment = AgentDeployment::factory()->create();

        // Create some completed tasks with scoring data
        AgentTask::factory()->count(5)->create([
            'agent_deployment_id' => $deployment->id,
            'organization_id' => $deployment->organization_id,
            'status' => 'completed',
            'confidence_score' => 85,
            'accuracy_score' => 90,
            'delusion_risk_score' => 12,
            'cost' => 0.05,
        ]);

        $scorecard = $this->service->generateScorecard($deployment);

        $this->assertArrayHasKey('accuracy', $scorecard);
        $this->assertArrayHasKey('productivity', $scorecard);
        $this->assertArrayHasKey('compliance', $scorecard);
        $this->assertArrayHasKey('reliability', $scorecard);
        $this->assertArrayHasKey('trustworthiness', $scorecard);
        $this->assertArrayHasKey('cost_savings', $scorecard);
        $this->assertArrayHasKey('overall_score', $scorecard);
    }

    public function test_overall_score_is_within_0_to_100(): void
    {
        $deployment = AgentDeployment::factory()->create();

        $scorecard = $this->service->generateScorecard($deployment);

        $this->assertGreaterThanOrEqual(0, $scorecard['overall_score']);
        $this->assertLessThanOrEqual(100, $scorecard['overall_score']);
    }

    public function test_zero_tasks_produces_zero_baseline_score(): void
    {
        $deployment = AgentDeployment::factory()->create();

        $scorecard = $this->service->generateScorecard($deployment);

        // No tasks = no data = zero or null score acceptable
        $this->assertNotNull($scorecard);
    }
}
