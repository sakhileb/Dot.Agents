<?php

namespace Tests\Feature\Actions\Agents;

use App\Actions\Agents\DecommissionDeploymentAction;
use App\Actions\Agents\PauseDeploymentAction;
use App\Actions\Agents\RateAgentTaskAction;
use App\Actions\Agents\ResumeDeploymentAction;
use App\Events\AgentDecommissioned;
use App\Events\AgentPaused;
use App\Events\AgentResumed;
use App\Models\AgentDeployment;
use App\Models\AgentTask;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AgentLifecycleActionsTest extends TestCase
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
        session(['current_organization_id' => $this->organization->id]);
        $this->deployment = AgentDeployment::factory()->create([
            'organization_id' => $this->organization->id,
            'status' => 'active',
            'deployed_by' => $this->user->id,
        ]);
        Gate::before(fn () => true);
    }

    // ── PauseDeploymentAction ──────────────────────────────────────────────

    public function test_pause_sets_status_to_paused(): void
    {
        $this->actingAs($this->user);

        $result = app(PauseDeploymentAction::class)->execute($this->deployment);

        $this->assertSame('paused', $result->status);
        $this->assertDatabaseHas('agent_deployments', [
            'id' => $this->deployment->id,
            'status' => 'paused',
        ]);
    }

    public function test_pause_fires_agent_paused_event(): void
    {
        $this->actingAs($this->user);
        Event::fake([AgentPaused::class]);

        app(PauseDeploymentAction::class)->execute($this->deployment);

        Event::assertDispatched(AgentPaused::class, fn ($e) => $e->deployment->id === $this->deployment->id);
    }

    public function test_pause_creates_audit_log(): void
    {
        $this->actingAs($this->user);

        app(PauseDeploymentAction::class)->execute($this->deployment);

        $this->assertDatabaseHas('audit_logs', [
            'organization_id' => $this->organization->id,
            'event' => 'deployment.paused',
        ]);
    }

    // ── ResumeDeploymentAction ─────────────────────────────────────────────

    public function test_resume_sets_status_to_active(): void
    {
        $this->actingAs($this->user);
        $this->deployment->update(['status' => 'paused']);

        $result = app(ResumeDeploymentAction::class)->execute($this->deployment);

        $this->assertSame('active', $result->status);
    }

    public function test_resume_creates_audit_log(): void
    {
        $this->actingAs($this->user);
        $this->deployment->update(['status' => 'paused']);

        Event::fake([AgentResumed::class]);

        app(ResumeDeploymentAction::class)->execute($this->deployment);

        Event::assertDispatched(AgentResumed::class, function ($event) {
            return $event->deployment->id === $this->deployment->id;
        });
    }

    // ── DecommissionDeploymentAction ───────────────────────────────────────

    public function test_decommission_sets_status_and_timestamp(): void
    {
        $this->actingAs($this->user);

        $result = app(DecommissionDeploymentAction::class)->execute($this->deployment, 'No longer needed');

        $this->assertSame('decommissioned', $result->status);
        $this->assertNotNull($result->decommissioned_at);
    }

    public function test_decommission_fires_agent_decommissioned_event(): void
    {
        $this->actingAs($this->user);
        Event::fake([AgentDecommissioned::class]);

        app(DecommissionDeploymentAction::class)->execute($this->deployment, 'Cost reduction');

        Event::assertDispatched(AgentDecommissioned::class, function ($e) {
            return $e->deployment->id === $this->deployment->id
                && $e->reason === 'Cost reduction';
        });
    }

    public function test_decommission_creates_audit_log(): void
    {
        $this->actingAs($this->user);

        app(DecommissionDeploymentAction::class)->execute($this->deployment, 'Reason');

        $this->assertDatabaseHas('audit_logs', [
            'organization_id' => $this->organization->id,
            'event' => 'deployment.decommissioned',
        ]);
    }

    // ── RateAgentTaskAction ────────────────────────────────────────────────

    public function test_rate_records_rating_and_feedback(): void
    {
        $this->actingAs($this->user);
        $task = AgentTask::factory()->create([
            'organization_id' => $this->organization->id,
            'agent_deployment_id' => $this->deployment->id,
            'status' => 'completed',
        ]);

        $result = app(RateAgentTaskAction::class)->execute($task, 4, 'Good work');

        $this->assertSame(4, $result->user_rating);
        $this->assertSame('Good work', $result->user_feedback);
        $this->assertNotNull($result->rated_at);
    }

    public function test_rate_rejects_out_of_range_rating(): void
    {
        $this->actingAs($this->user);
        $task = AgentTask::factory()->create([
            'organization_id' => $this->organization->id,
            'agent_deployment_id' => $this->deployment->id,
        ]);

        $this->expectException(ValidationException::class);

        app(RateAgentTaskAction::class)->execute($task, 6);
    }

    public function test_rate_prevents_double_rating(): void
    {
        $this->actingAs($this->user);
        $task = AgentTask::factory()->create([
            'organization_id' => $this->organization->id,
            'agent_deployment_id' => $this->deployment->id,
            'rated_at' => now(),
            'user_rating' => 3,
        ]);

        $this->expectException(ValidationException::class);

        app(RateAgentTaskAction::class)->execute($task, 5);
    }
}
