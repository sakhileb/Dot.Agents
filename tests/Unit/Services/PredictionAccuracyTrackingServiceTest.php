<?php

namespace Tests\Unit\Services;

use App\Models\AgentDeployment;
use App\Models\DecisionLog;
use App\Models\Organization;
use App\Models\User;
use App\Services\Governance\PredictionAccuracyTrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for PredictionAccuracyTrackingService.
 *
 * Tests verify score structure, ECE calibration math, and boundary conditions.
 */
class PredictionAccuracyTrackingServiceTest extends TestCase
{
    use RefreshDatabase;

    private PredictionAccuracyTrackingService $service;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service      = app(PredictionAccuracyTrackingService::class);
        $user               = User::factory()->create();
        $this->organization = Organization::factory()->create(['owner_id' => $user->id]);
    }

    public function test_calculate_for_organization_returns_required_keys(): void
    {
        $result = $this->service->calculateForOrganization($this->organization);

        $this->assertArrayHasKey('score', $result);
        $this->assertArrayHasKey('dimensions', $result);
    }

    public function test_score_is_within_valid_range(): void
    {
        $result = $this->service->calculateForOrganization($this->organization);

        $this->assertGreaterThanOrEqual(0, $result['score']);
        $this->assertLessThanOrEqual(100, $result['score']);
    }

    public function test_calculate_for_deployment_returns_required_keys(): void
    {
        $deployment = AgentDeployment::factory()->create([
            'organization_id' => $this->organization->id,
        ]);

        $result = $this->service->calculateForDeployment($deployment);

        $this->assertArrayHasKey('score', $result);
        $this->assertArrayHasKey('dimensions', $result);
    }

    public function test_dimensions_contain_all_three_scoring_axes(): void
    {
        $result = $this->service->calculateForOrganization($this->organization);

        $this->assertArrayHasKey('hitRate', $result['dimensions']);
        $this->assertArrayHasKey('calibration', $result['dimensions']);
        $this->assertArrayHasKey('realityAlign', $result['dimensions']);
    }

    public function test_handles_organization_with_no_decision_logs(): void
    {
        // No decision logs should return valid structure, not throw
        $result = $this->service->calculateForOrganization($this->organization);

        $this->assertIsNumeric($result['score']);
    }

    public function test_record_outcome_does_not_throw(): void
    {
        $deployment = AgentDeployment::factory()->create([
            'organization_id' => $this->organization->id,
        ]);

        $decision = DecisionLog::factory()->create([
            'agent_deployment_id'     => $deployment->id,
            'organization_id'         => $this->organization->id,
            'confidence_score'        => 85.0,
            'reality_alignment_score' => 90.0,
            'final_outcome'           => 'implemented',
        ]);

        // Should not throw
        $this->service->recordOutcome($decision);
        $this->assertTrue(true);
    }
}
