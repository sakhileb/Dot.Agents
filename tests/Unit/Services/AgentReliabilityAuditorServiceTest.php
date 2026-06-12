<?php

namespace Tests\Unit\Services;

use App\Models\AgentDeployment;
use App\Models\AgentTask;
use App\Models\Organization;
use App\Models\User;
use App\Services\Governance\AgentReliabilityAuditorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for AgentReliabilityAuditorService.
 *
 * Tests verify score structure, result shape, and behavior with no/some data.
 */
class AgentReliabilityAuditorServiceTest extends TestCase
{
    use RefreshDatabase;

    private AgentReliabilityAuditorService $service;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service      = app(AgentReliabilityAuditorService::class);
        $user               = User::factory()->create();
        $this->organization = Organization::factory()->create(['owner_id' => $user->id]);
    }

    public function test_audit_deployment_returns_required_keys(): void
    {
        $deployment = AgentDeployment::factory()->create([
            'organization_id' => $this->organization->id,
        ]);

        $result = $this->service->auditDeployment($deployment);

        $this->assertArrayHasKey('score', $result);
        $this->assertArrayHasKey('gate_pass', $result);
        $this->assertIsNumeric($result['score']);
    }

    public function test_score_is_within_valid_range(): void
    {
        $deployment = AgentDeployment::factory()->create([
            'organization_id' => $this->organization->id,
        ]);

        $result = $this->service->auditDeployment($deployment);

        $this->assertGreaterThanOrEqual(0, $result['score']);
        $this->assertLessThanOrEqual(100, $result['score']);
    }

    public function test_audit_organization_returns_aggregate(): void
    {
        AgentDeployment::factory()->count(2)->create([
            'organization_id' => $this->organization->id,
            'status'          => 'active',
        ]);

        $result = $this->service->auditOrganization($this->organization);

        $this->assertArrayHasKey('score', $result);
        $this->assertArrayHasKey('total_deployments', $result);
    }

    public function test_audit_organization_handles_no_deployments(): void
    {
        // Organization with no deployments should not throw
        $result = $this->service->auditOrganization($this->organization);

        $this->assertArrayHasKey('score', $result);
        $this->assertGreaterThanOrEqual(0, $result['score']);
    }

    public function test_deployment_with_all_completed_tasks_scores_high(): void
    {
        $deployment = AgentDeployment::factory()->create([
            'organization_id' => $this->organization->id,
            'status'          => 'active',
        ]);

        AgentTask::factory()->count(10)->create([
            'agent_deployment_id' => $deployment->id,
            'organization_id'     => $this->organization->id,
            'status'              => 'completed',
            'created_at'          => now()->subDays(5),
        ]);

        $this->service->invalidate($deployment);
        $result = $this->service->auditDeployment($deployment);

        // With all completed tasks, reliability should be high
        $this->assertGreaterThan(50, $result['score']);
    }
}
