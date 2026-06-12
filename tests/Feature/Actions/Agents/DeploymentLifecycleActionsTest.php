<?php

namespace Tests\Feature\Actions\Agents;

use App\Actions\Agents\DecommissionDeploymentAction;
use App\Actions\Agents\PauseDeploymentAction;
use App\Actions\Agents\StartAgentChatSessionAction;
use App\DTOs\Agents\StartAgentChatSessionData;
use App\Actions\Agents\UpdateDeploymentAction;
use App\DTOs\Agents\UpdateDeploymentData;
use App\Models\AgentDeployment;
use App\Models\AgentSession;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class DeploymentLifecycleActionsTest extends TestCase
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
        ]);
        Gate::before(fn () => true);
    }

    // ── PauseDeploymentAction ──────────────────────────────────────────────

    public function test_pause_deployment_sets_status_paused(): void
    {
        $this->actingAs($this->user);

        $result = app(PauseDeploymentAction::class)->execute($this->deployment);

        $this->assertSame('paused', $result->status);
        $this->assertDatabaseHas('agent_deployments', [
            'id' => $this->deployment->id,
            'status' => 'paused',
        ]);
    }

    public function test_pause_deployment_creates_audit_log(): void
    {
        $this->actingAs($this->user);

        app(PauseDeploymentAction::class)->execute($this->deployment);

        $this->assertDatabaseHas('audit_logs', [
            'organization_id' => $this->organization->id,
            'event' => 'deployment.paused',
        ]);
    }

    // ── DecommissionDeploymentAction ──────────────────────────────────────

    public function test_decommission_sets_status_decommissioned(): void
    {
        $this->actingAs($this->user);

        $result = app(DecommissionDeploymentAction::class)
            ->execute($this->deployment, 'No longer needed');

        $this->assertSame('decommissioned', $result->status);
        $this->assertNotNull($result->decommissioned_at);
    }

    public function test_decommission_creates_audit_log(): void
    {
        $this->actingAs($this->user);

        app(DecommissionDeploymentAction::class)->execute($this->deployment, 'Cost reduction');

        $this->assertDatabaseHas('audit_logs', [
            'organization_id' => $this->organization->id,
            'event' => 'deployment.decommissioned',
        ]);
    }

    // ── UpdateDeploymentAction ────────────────────────────────────────────

    public function test_update_deployment_changes_allowed_fields(): void
    {
        $this->actingAs($this->user);

        $result = app(UpdateDeploymentAction::class)->execute(
            $this->deployment,
            new UpdateDeploymentData(name: 'Updated Name', deploymentMode: 'semi-autonomous', confidenceThreshold: 85.0)
        );

        $this->assertSame('Updated Name', $result->name);
        $this->assertSame('semi-autonomous', $result->deployment_mode);
        $this->assertSame(85.0, (float) $result->confidence_threshold);
    }

    public function test_update_deployment_ignores_disallowed_fields(): void
    {
        $this->actingAs($this->user);
        $originalOrgId = $this->deployment->organization_id;

        app(UpdateDeploymentAction::class)->execute(
            $this->deployment,
            new UpdateDeploymentData(name: 'Allowed')
        );

        $this->assertSame($originalOrgId, $this->deployment->fresh()->organization_id);
    }

    public function test_update_deployment_creates_audit_log(): void
    {
        $this->actingAs($this->user);

        app(UpdateDeploymentAction::class)->execute($this->deployment, new UpdateDeploymentData(name: 'New Name'));

        $this->assertDatabaseHas('audit_logs', [
            'organization_id' => $this->organization->id,
            'event' => 'deployment.updated',
        ]);
    }

    // ── StartAgentChatSessionAction ───────────────────────────────────────

    public function test_start_chat_session_creates_session_record(): void
    {
        $this->actingAs($this->user);

        $session = app(StartAgentChatSessionAction::class)
            ->execute($this->deployment, new StartAgentChatSessionData(
                userId: $this->user->id,
                agentDeploymentId: $this->deployment->id,
                organizationId: $this->organization->id,
            ));

        $this->assertInstanceOf(AgentSession::class, $session);
        $this->assertSame($this->deployment->id, $session->agent_deployment_id);
        $this->assertSame($this->user->id, $session->user_id);
        $this->assertSame('active', $session->status);
    }

    public function test_start_chat_session_scoped_to_organization(): void
    {
        $this->actingAs($this->user);

        $session = app(StartAgentChatSessionAction::class)
            ->execute($this->deployment, new StartAgentChatSessionData(
                userId: $this->user->id,
                agentDeploymentId: $this->deployment->id,
                organizationId: $this->organization->id,
            ));

        $this->assertSame($this->organization->id, $session->organization_id);
    }
}
