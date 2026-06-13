<?php

namespace Tests\Unit\Services;

use App\Models\AgentDeployment;
use App\Models\AgentTask;
use App\Models\Organization;
use App\Models\User;
use App\Services\Governance\AuditService;
use App\Services\Governance\DigitalImmuneSystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Unit tests for DigitalImmuneSystem::checkDeployment.
 *
 * Tests verify the health status structure and boundary detection logic.
 */
class DigitalImmuneSystemUnitTest extends TestCase
{
    use RefreshDatabase;

    private DigitalImmuneSystem $dis;

    protected function setUp(): void
    {
        parent::setUp();

        $auditService = Mockery::mock(AuditService::class);
        $auditService->shouldReceive('logAgentAction')->andReturn(null);
        $auditService->shouldReceive('logUserAction')->andReturn(null);

        $this->dis = new DigitalImmuneSystem($auditService);
    }

    private function makeDeployment(array $overrides = []): AgentDeployment
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create(['owner_id' => $user->id]);

        return AgentDeployment::factory()->create(array_merge([
            'organization_id' => $org->id,
            'status' => 'active',
        ], $overrides));
    }

    #[Test]
    public function check_deployment_returns_required_keys(): void
    {
        $deployment = $this->makeDeployment();

        $result = $this->dis->checkDeployment($deployment);

        $this->assertArrayHasKey('health', $result);
        $this->assertArrayHasKey('events', $result);
        $this->assertContains($result['health'], ['healthy', 'warnings', 'critical', 'quarantined']);
    }

    #[Test]
    public function check_deployment_returns_healthy_when_no_anomalies(): void
    {
        $deployment = $this->makeDeployment();

        $result = $this->dis->checkDeployment($deployment);

        // A brand-new deployment with no tasks/failures should be healthy
        $this->assertSame('healthy', $result['health']);
        $this->assertEmpty($result['events']);
    }

    #[Test]
    public function check_deployment_detects_high_failure_rate(): void
    {
        $deployment = $this->makeDeployment();

        // Create 8 failed tasks and 2 completed to produce 80% failure rate (>50% threshold)
        AgentTask::factory()->count(8)->create([
            'agent_deployment_id' => $deployment->id,
            'organization_id' => $deployment->organization_id,
            'status' => 'failed',
            'created_at' => now()->subMinutes(5),
        ]);

        AgentTask::factory()->count(2)->create([
            'agent_deployment_id' => $deployment->id,
            'organization_id' => $deployment->organization_id,
            'status' => 'completed',
            'created_at' => now()->subMinutes(5),
        ]);

        $result = $this->dis->checkDeployment($deployment);

        $this->assertNotSame('healthy', $result['health']);
    }

    #[Test]
    public function run_health_check_returns_correct_aggregate_structure(): void
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create(['owner_id' => $user->id]);

        AgentDeployment::factory()->count(2)->create([
            'organization_id' => $org->id,
            'status' => 'active',
        ]);

        $report = $this->dis->runHealthCheck($org->id);

        $this->assertArrayHasKey('total_agents', $report);
        $this->assertArrayHasKey('healthy', $report);
        $this->assertArrayHasKey('warnings', $report);
        $this->assertArrayHasKey('critical', $report);
        $this->assertArrayHasKey('quarantined', $report);
        $this->assertSame(2, $report['total_agents']);
        $this->assertSame(
            $report['total_agents'],
            $report['healthy'] + $report['warnings'] + $report['critical'] + $report['quarantined'],
            'Sum of health categories must equal total_agents'
        );
    }
}
