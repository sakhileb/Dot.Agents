<?php

namespace Tests\Feature\Actions;

use App\Actions\Agents\PauseDeploymentAction;
use App\Actions\Agents\ResumeDeploymentAction;
use App\Models\AgentDeployment;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class PauseResumeDeploymentActionTest extends TestCase
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
            'deployed_by' => $this->user->id,
            'status' => 'active',
        ]);
    }

    public function test_pause_sets_deployment_status_to_paused(): void
    {
        $this->actingAs($this->user);
        Gate::before(fn () => true);

        $result = app(PauseDeploymentAction::class)->execute($this->deployment);

        $this->assertEquals('paused', $result->status);
        $this->assertDatabaseHas('agent_deployments', [
            'id' => $this->deployment->id,
            'status' => 'paused',
        ]);
    }

    public function test_resume_sets_deployment_status_to_active(): void
    {
        $this->deployment->update(['status' => 'paused']);
        $this->actingAs($this->user);
        Gate::before(fn () => true);

        $result = app(ResumeDeploymentAction::class)->execute($this->deployment);

        $this->assertEquals('active', $result->status);
        $this->assertDatabaseHas('agent_deployments', [
            'id' => $this->deployment->id,
            'status' => 'active',
        ]);
    }

    public function test_pause_requires_authorization(): void
    {
        $this->actingAs($this->user);
        // No Gate::before — real policy applies; user has no org relationship
        $otherOrg = Organization::factory()->create();
        $otherDeployment = AgentDeployment::factory()->create([
            'organization_id' => $otherOrg->id,
            'status' => 'active',
        ]);

        $this->expectException(AuthorizationException::class);
        app(PauseDeploymentAction::class)->execute($otherDeployment);
    }

    public function test_resume_requires_authorization(): void
    {
        $this->actingAs($this->user);
        $otherOrg = Organization::factory()->create();
        $otherDeployment = AgentDeployment::factory()->create([
            'organization_id' => $otherOrg->id,
            'status' => 'paused',
        ]);

        $this->expectException(AuthorizationException::class);
        app(ResumeDeploymentAction::class)->execute($otherDeployment);
    }
}
