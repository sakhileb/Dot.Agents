<?php

namespace Tests\Feature\Actions\Agents;

use App\Actions\Agents\DecommissionDeploymentAction;
use App\Events\AgentDecommissioned;
use App\Models\AgentDeployment;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DecommissionDeploymentActionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->organization = Organization::factory()->create(['owner_id' => $this->user->id]);
        $this->actingAs($this->user);
        Gate::before(fn () => true);
        session(['current_organization_id' => $this->organization->id]);
    }

    #[Test]
    public function test_decommissions_deployment(): void
    {
        Event::fake([AgentDecommissioned::class]);

        $deployment = AgentDeployment::factory()->create([
            'organization_id' => $this->organization->id,
            'status' => 'active',
        ]);

        app(DecommissionDeploymentAction::class)->execute($deployment);

        $this->assertDatabaseHas('agent_deployments', [
            'id' => $deployment->id,
            'status' => 'decommissioned',
        ]);
        Event::assertDispatched(AgentDecommissioned::class);
    }

    #[Test]
    public function test_decommissioned_at_is_set(): void
    {
        Event::fake();

        $deployment = AgentDeployment::factory()->create([
            'organization_id' => $this->organization->id,
            'status' => 'active',
        ]);

        app(DecommissionDeploymentAction::class)->execute($deployment);

        $this->assertNotNull($deployment->fresh()->decommissioned_at);
    }
}
