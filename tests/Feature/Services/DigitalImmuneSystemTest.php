<?php

namespace Tests\Feature\Services;

use App\Models\AgentDeployment;
use App\Models\DecisionLog;
use App\Models\Organization;
use App\Models\User;
use App\Services\Governance\DigitalImmuneSystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DigitalImmuneSystemTest extends TestCase
{
    use RefreshDatabase;

    private DigitalImmuneSystem $dis;

    private Organization $organization;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->organization = Organization::factory()->create(['owner_id' => $this->user->id]);
        session(['current_organization_id' => $this->organization->id]);
        Cache::flush();

        $this->dis = app(DigitalImmuneSystem::class);
    }

    public function test_health_check_returns_report_structure(): void
    {
        $report = $this->dis->runHealthCheck($this->organization->id);

        $this->assertArrayHasKey('checked_at', $report);
        $this->assertArrayHasKey('total_agents', $report);
        $this->assertArrayHasKey('healthy', $report);
        $this->assertArrayHasKey('warnings', $report);
        $this->assertArrayHasKey('critical', $report);
        $this->assertArrayHasKey('events', $report);
    }

    public function test_health_check_with_no_active_deployments_reports_zero(): void
    {
        $report = $this->dis->runHealthCheck($this->organization->id);

        $this->assertSame(0, $report['total_agents']);
        $this->assertSame(0, $report['healthy']);
    }

    public function test_check_deployment_returns_health_status(): void
    {
        $deployment = AgentDeployment::factory()->create([
            'organization_id' => $this->organization->id,
            'status' => 'active',
        ]);

        $result = $this->dis->checkDeployment($deployment);

        $this->assertArrayHasKey('health', $result);
        $this->assertArrayHasKey('events', $result);
        $this->assertArrayHasKey('deployment_id', $result);
        $this->assertSame($deployment->id, $result['deployment_id']);
    }

    public function test_high_delusion_rate_marks_deployment_critical(): void
    {
        $deployment = AgentDeployment::factory()->create([
            'organization_id' => $this->organization->id,
            'status' => 'active',
        ]);

        // Create 5 high-risk decision logs in last 24h
        for ($i = 0; $i < 5; $i++) {
            DecisionLog::factory()->create([
                'agent_deployment_id' => $deployment->id,
                'organization_id' => $this->organization->id,
                'delusion_risk_score' => 75,
                'created_at' => now()->subHours(2),
            ]);
        }

        $result = $this->dis->checkDeployment($deployment);

        $this->assertSame('critical', $result['health']);
        $types = array_column($result['events'], 'type');
        $this->assertContains('high_delusion_rate', $types);
    }

    public function test_healthy_deployment_has_no_events(): void
    {
        $deployment = AgentDeployment::factory()->create([
            'organization_id' => $this->organization->id,
            'status' => 'active',
        ]);

        // No anomalies — fresh deployment
        $result = $this->dis->checkDeployment($deployment);

        $this->assertSame('healthy', $result['health']);
    }
}
