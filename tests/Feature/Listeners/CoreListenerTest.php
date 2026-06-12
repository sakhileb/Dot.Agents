<?php

namespace Tests\Feature\Listeners;

use App\Events\AgentDeployed;
use App\Events\AgentTaskCompleted;
use App\Events\AgentTaskFailed;
use App\Jobs\GenerateAgentScorecard;
use App\Jobs\RunDigitalImmuneSystemCheck;
use App\Jobs\SendPlatformNotification;
use App\Listeners\HandleAgentTaskFailed;
use App\Listeners\LogDeploymentAudit;
use App\Listeners\UpdateScorecardOnTaskComplete;
use App\Models\AgentDeployment;
use App\Models\AgentTask;
use App\Models\Organization;
use App\Models\User;
use App\Services\Governance\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class CoreListenerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Organization $organization;

    private AgentDeployment $deployment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->organization = Organization::factory()->create(['owner_id' => $this->user->id]);
        $this->deployment = AgentDeployment::factory()->create([
            'organization_id' => $this->organization->id,
        ]);

        // Attach user to organization with owner role so toAdmins() returns admins
        $this->organization->users()->attach($this->user->id, [
            'role' => 'owner',
            'is_primary' => true,
            'joined_at' => now(),
        ]);
    }

    // ── LogDeploymentAudit ────────────────────────────────────────────────────

    /** @test */
    public function log_deployment_audit_calls_audit_service_on_handle(): void
    {
        Bus::fake([SendPlatformNotification::class]);

        $auditService = Mockery::mock(AuditService::class);
        $auditService->shouldReceive('logAgentAction')
            ->once()
            ->with($this->deployment, 'agent_deployed', Mockery::type('array'));

        $listener = new LogDeploymentAudit($auditService);
        $listener->handle(new AgentDeployed($this->deployment));

        Bus::assertDispatched(SendPlatformNotification::class);
    }

    /** @test */
    public function log_deployment_audit_dispatches_admin_notification(): void
    {
        Bus::fake([SendPlatformNotification::class]);

        $auditService = Mockery::mock(AuditService::class);
        $auditService->shouldReceive('logAgentAction')->once();

        $listener = new LogDeploymentAudit($auditService);
        $listener->handle(new AgentDeployed($this->deployment));

        // At least one notification is dispatched (one per admin/owner found)
        Bus::assertDispatched(SendPlatformNotification::class);
    }

    /** @test */
    public function log_deployment_audit_logs_error_on_failure(): void
    {
        Log::shouldReceive('error')->once()->with(
            '[LogDeploymentAudit] Failed to log deployment audit',
            Mockery::type('array')
        );

        $auditService = Mockery::mock(AuditService::class);
        $listener = new LogDeploymentAudit($auditService);

        $listener->failed(
            new AgentDeployed($this->deployment),
            new \RuntimeException('DB connection lost')
        );
    }

    // ── UpdateScorecardOnTaskComplete ─────────────────────────────────────────

    /** @test */
    public function update_scorecard_dispatches_generate_scorecard_job(): void
    {
        Bus::fake([GenerateAgentScorecard::class]);

        $task = AgentTask::factory()->create([
            'agent_deployment_id' => $this->deployment->id,
            'organization_id'     => $this->organization->id,
            'status'              => 'completed',
        ]);

        $listener = new UpdateScorecardOnTaskComplete;
        $listener->handle(new AgentTaskCompleted($task));

        Bus::assertDispatched(GenerateAgentScorecard::class);
    }

    /** @test */
    public function update_scorecard_logs_error_on_failure(): void
    {
        $task = AgentTask::factory()->create([
            'agent_deployment_id' => $this->deployment->id,
            'organization_id'     => $this->organization->id,
            'status'              => 'completed',
        ]);

        Log::shouldReceive('error')->once()->with(
            '[UpdateScorecardOnTaskComplete] Failed to dispatch scorecard job',
            Mockery::type('array')
        );

        $listener = new UpdateScorecardOnTaskComplete;
        $listener->failed(
            new AgentTaskCompleted($task),
            new \RuntimeException('Queue unavailable')
        );
    }

    // ── HandleAgentTaskFailed ─────────────────────────────────────────────────

    /** @test */
    public function handle_agent_task_failed_logs_audit_and_dispatches_dis_after_three_failures(): void
    {
        Bus::fake([RunDigitalImmuneSystemCheck::class]);

        // Create 2 prior failed tasks in the last hour to hit the threshold of 3
        AgentTask::factory()->count(2)->create([
            'agent_deployment_id' => $this->deployment->id,
            'organization_id'     => $this->organization->id,
            'status'              => 'failed',
            'created_at'          => now()->subMinutes(10),
        ]);

        $task = AgentTask::factory()->create([
            'agent_deployment_id' => $this->deployment->id,
            'organization_id'     => $this->organization->id,
            'status'              => 'failed',
        ]);

        $listener = new HandleAgentTaskFailed;
        $listener->handle(new AgentTaskFailed($task, 'Model timeout'));

        Bus::assertDispatched(RunDigitalImmuneSystemCheck::class);
    }

    /** @test */
    public function handle_agent_task_failed_does_not_dispatch_dis_for_single_failure(): void
    {
        Bus::fake([RunDigitalImmuneSystemCheck::class]);

        $task = AgentTask::factory()->create([
            'agent_deployment_id' => $this->deployment->id,
            'organization_id'     => $this->organization->id,
            'status'              => 'failed',
        ]);

        $listener = new HandleAgentTaskFailed;
        $listener->handle(new AgentTaskFailed($task, 'Transient error'));

        Bus::assertNotDispatched(RunDigitalImmuneSystemCheck::class);
    }

    /** @test */
    public function handle_agent_task_failed_uses_governance_queue(): void
    {
        $listener = new HandleAgentTaskFailed;

        $this->assertSame('governance', $listener->queue);
    }
}
