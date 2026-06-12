<?php

namespace Tests\Feature\Actions\Security;

use App\Actions\Security\EmergencyKillSwitchAction;
use App\Models\AgentDeployment;
use App\Models\AgentTask;
use App\Models\AgentWorkflow;
use App\Models\Organization;
use App\Models\User;
use App\Models\WorkflowExecution;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class EmergencyKillSwitchActionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Organization $organization;

    private AgentDeployment $deployment;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'platform_admin']);
        $this->user = User::factory()->create();
        $this->organization = Organization::factory()->create(['owner_id' => $this->user->id]);
        $this->deployment = AgentDeployment::factory()->create([
            'organization_id' => $this->organization->id,
            'status' => 'active',
        ]);
        $this->actingAs($this->user);
        Gate::before(fn () => true);
        session(['current_organization_id' => $this->organization->id]);
    }

    // ── killDeployment ────────────────────────────────────────────────────────

    public function test_kill_deployment_suspends_status(): void
    {
        app(EmergencyKillSwitchAction::class)->killDeployment($this->deployment, 'Test kill');

        $this->assertDatabaseHas('agent_deployments', [
            'id' => $this->deployment->id,
            'status' => 'suspended',
        ]);
    }

    public function test_kill_deployment_aborts_in_progress_tasks(): void
    {
        AgentTask::factory()->count(2)->create([
            'agent_deployment_id' => $this->deployment->id,
            'organization_id' => $this->organization->id,
            'status' => 'in_progress',
        ]);
        AgentTask::factory()->create([
            'agent_deployment_id' => $this->deployment->id,
            'organization_id' => $this->organization->id,
            'status' => 'pending',
        ]);

        app(EmergencyKillSwitchAction::class)->killDeployment($this->deployment, 'Emergency');

        $this->assertDatabaseMissing('agent_tasks', [
            'agent_deployment_id' => $this->deployment->id,
            'status' => 'in_progress',
        ]);
        $this->assertDatabaseMissing('agent_tasks', [
            'agent_deployment_id' => $this->deployment->id,
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('agent_tasks', [
            'agent_deployment_id' => $this->deployment->id,
            'status' => 'aborted',
        ]);
    }

    public function test_kill_deployment_creates_audit_log(): void
    {
        app(EmergencyKillSwitchAction::class)->killDeployment($this->deployment, 'Security incident');

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'security.kill_switch.deployment',
            'organization_id' => $this->organization->id,
        ]);
    }

    // ── killAllWorkflows ──────────────────────────────────────────────────────

    public function test_kill_all_workflows_aborts_running_executions(): void
    {
        $workflow = AgentWorkflow::factory()->active()->create([
            'organization_id' => $this->organization->id,
        ]);

        WorkflowExecution::create([
            'uuid' => (string) Str::uuid(),
            'workflow_id' => $workflow->id,
            'organization_id' => $this->organization->id,
            'triggered_by' => $this->user->id,
            'status' => 'running',
            'started_at' => now(),
        ]);

        $affected = app(EmergencyKillSwitchAction::class)->killAllWorkflows($this->organization, 'Halt all');

        $this->assertGreaterThanOrEqual(1, $affected);
        $this->assertDatabaseHas('workflow_executions', [
            'workflow_id' => $workflow->id,
            'status' => 'aborted',
        ]);
    }

    public function test_kill_all_workflows_pauses_active_workflows(): void
    {
        AgentWorkflow::factory()->active()->create([
            'organization_id' => $this->organization->id,
        ]);

        app(EmergencyKillSwitchAction::class)->killAllWorkflows($this->organization, 'Halt all');

        $this->assertDatabaseMissing('agent_workflows', [
            'organization_id' => $this->organization->id,
            'status' => 'active',
        ]);
    }

    public function test_kill_all_workflows_creates_audit_log(): void
    {
        app(EmergencyKillSwitchAction::class)->killAllWorkflows($this->organization, 'Security halt');

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'security.kill_switch.workflows',
            'organization_id' => $this->organization->id,
        ]);
    }

    // ── suspendOrganization ───────────────────────────────────────────────────

    public function test_suspend_organization_requires_platform_admin_role(): void
    {
        // Regular user without platform_admin role should be denied with 403
        $this->expectException(HttpException::class);
        app(EmergencyKillSwitchAction::class)->suspendOrganization($this->organization, 'Billing lapse');
    }

    public function test_suspend_organization_suspends_all_active_agents(): void
    {
        $this->user->assignRole('platform_admin');

        AgentDeployment::factory()->count(2)->create([
            'organization_id' => $this->organization->id,
            'status' => 'active',
        ]);

        app(EmergencyKillSwitchAction::class)->suspendOrganization($this->organization, 'Test suspension');

        $this->assertDatabaseMissing('agent_deployments', [
            'organization_id' => $this->organization->id,
            'status' => 'active',
        ]);
    }

    public function test_suspend_organization_creates_audit_log(): void
    {
        $this->user->assignRole('platform_admin');

        app(EmergencyKillSwitchAction::class)->suspendOrganization($this->organization, 'Compliance violation');

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'security.kill_switch.organization',
            'organization_id' => $this->organization->id,
        ]);
    }

    public function test_suspend_organization_returns_stats(): void
    {
        $this->user->assignRole('platform_admin');

        AgentDeployment::factory()->count(3)->create([
            'organization_id' => $this->organization->id,
            'status' => 'active',
        ]);

        $stats = app(EmergencyKillSwitchAction::class)->suspendOrganization($this->organization, 'Test');

        $this->assertArrayHasKey('agents_suspended', $stats);
        $this->assertArrayHasKey('workflows_halted', $stats);
        $this->assertArrayHasKey('executions_aborted', $stats);
        $this->assertGreaterThanOrEqual(3, $stats['agents_suspended']);
    }
}
