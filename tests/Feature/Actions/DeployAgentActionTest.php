<?php

namespace Tests\Feature\Actions;

use App\Actions\Agents\DeployAgentAction;
use App\DTOs\Agents\DeployAgentData;
use App\Events\AgentDeployed;
use App\Models\Agent;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class DeployAgentActionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Organization $organization;

    private Agent $agent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->organization = Organization::factory()->create(['owner_id' => $this->user->id]);
        $this->agent = Agent::factory()->create();
    }

    public function test_deploys_agent_and_persists_to_database(): void
    {
        Event::fake();
        $this->actingAs($this->user);
        Gate::before(fn () => true); // bypass policy for this test

        $data = new DeployAgentData(
            agentId: $this->agent->id,
            organizationId: $this->organization->id,
            deployedBy: $this->user->id,
            name: 'Finance Analyst',
            deploymentMode: 'advisory',
            confidenceThreshold: 80.0,
        );

        $deployment = app(DeployAgentAction::class)->execute($data);

        $this->assertDatabaseHas('agent_deployments', [
            'id' => $deployment->id,
            'organization_id' => $this->organization->id,
            'agent_id' => $this->agent->id,
            'name' => 'Finance Analyst',
            'deployment_mode' => 'advisory',
            'status' => 'active',
        ]);
    }

    public function test_fires_agent_deployed_event(): void
    {
        Event::fake();
        $this->actingAs($this->user);
        Gate::before(fn () => true);

        $data = new DeployAgentData(
            agentId: $this->agent->id,
            organizationId: $this->organization->id,
            deployedBy: $this->user->id,
            name: 'Test Deploy',
            deploymentMode: 'advisory',
        );

        $deployment = app(DeployAgentAction::class)->execute($data);

        Event::assertDispatched(AgentDeployed::class, fn ($e) => $e->deployment->id === $deployment->id);
    }

    public function test_advisory_mode_requires_human_approval(): void
    {
        Event::fake();
        $this->actingAs($this->user);
        Gate::before(fn () => true);

        $data = new DeployAgentData(
            agentId: $this->agent->id,
            organizationId: $this->organization->id,
            deployedBy: $this->user->id,
            name: 'Advisory Agent',
            deploymentMode: 'advisory',
        );

        $deployment = app(DeployAgentAction::class)->execute($data);

        $this->assertTrue($deployment->requires_human_approval);
    }

    public function test_increments_agent_deployment_count(): void
    {
        Event::fake();
        $this->actingAs($this->user);
        Gate::before(fn () => true);

        $before = $this->agent->total_deployments;

        $data = new DeployAgentData(
            agentId: $this->agent->id,
            organizationId: $this->organization->id,
            deployedBy: $this->user->id,
            name: 'Count Test',
            deploymentMode: 'advisory',
        );

        app(DeployAgentAction::class)->execute($data);

        $this->assertEquals($before + 1, $this->agent->fresh()->total_deployments);
    }

    public function test_unauthorized_user_cannot_deploy(): void
    {
        $this->actingAs($this->user);

        $data = new DeployAgentData(
            agentId: $this->agent->id,
            organizationId: $this->organization->id,
            deployedBy: $this->user->id,
            name: 'Unauthorized',
            deploymentMode: 'advisory',
        );

        $this->expectException(AuthorizationException::class);

        app(DeployAgentAction::class)->execute($data);
    }
}
