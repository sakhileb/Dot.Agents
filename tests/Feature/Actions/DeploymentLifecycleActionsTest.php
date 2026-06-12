<?php

namespace Tests\Feature\Actions;

use App\Actions\Agents\DecommissionDeploymentAction;
use App\Actions\Agents\PauseDeploymentAction;
use App\Actions\Agents\ResumeDeploymentAction;
use App\Events\AgentResumed;
use App\Models\AgentDeployment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class DeploymentLifecycleActionsTest extends TestCase
{
    use RefreshDatabase;

    private AgentDeployment $deployment;

    protected function setUp(): void
    {
        parent::setUp();
        $user = User::factory()->create();
        $this->actingAs($user);
        Gate::before(fn () => true); // bypass for unit testing
        $this->deployment = AgentDeployment::factory()->create(['status' => 'active']);
    }

    public function test_pause_action_sets_status_to_paused(): void
    {
        app(PauseDeploymentAction::class)->execute($this->deployment);

        $this->assertDatabaseHas('agent_deployments', [
            'id' => $this->deployment->id,
            'status' => 'paused',
        ]);
    }

    public function test_pause_action_logs_audit_event(): void
    {
        app(PauseDeploymentAction::class)->execute($this->deployment);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'deployment.paused',
        ]);
    }

    public function test_resume_action_sets_status_to_active(): void
    {
        $this->deployment->update(['status' => 'paused']);

        app(ResumeDeploymentAction::class)->execute($this->deployment);

        $this->assertDatabaseHas('agent_deployments', [
            'id' => $this->deployment->id,
            'status' => 'active',
        ]);
    }

    public function test_resume_action_logs_audit_event(): void
    {
        $this->deployment->update(['status' => 'paused']);

        Event::fake([AgentResumed::class]);

        app(ResumeDeploymentAction::class)->execute($this->deployment);

        Event::assertDispatched(AgentResumed::class);
    }

    public function test_decommission_action_sets_status_to_decommissioned(): void
    {
        app(DecommissionDeploymentAction::class)->execute($this->deployment, 'End of project');

        $this->assertDatabaseHas('agent_deployments', [
            'id' => $this->deployment->id,
            'status' => 'decommissioned',
        ]);
    }

    public function test_decommission_action_logs_reason_in_audit(): void
    {
        app(DecommissionDeploymentAction::class)->execute($this->deployment, 'End of project');

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'deployment.decommissioned',
        ]);
    }
}
