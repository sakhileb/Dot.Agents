<?php

namespace Tests\Feature\Actions\Agents;

use App\Actions\Agents\PauseDeploymentAction;
use App\Events\AgentPaused;
use App\Models\AgentDeployment;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PauseDeploymentActionTest extends TestCase
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
    public function test_pauses_active_deployment(): void
    {
        Event::fake([AgentPaused::class]);

        $deployment = AgentDeployment::factory()->create([
            'organization_id' => $this->organization->id,
            'status' => 'active',
        ]);

        $result = app(PauseDeploymentAction::class)->execute($deployment);

        $this->assertEquals('paused', $result->status);
        $this->assertDatabaseHas('agent_deployments', [
            'id' => $deployment->id,
            'status' => 'paused',
        ]);
        Event::assertDispatched(AgentPaused::class);
    }

    #[Test]
    public function test_returns_refreshed_deployment(): void
    {
        Event::fake();

        $deployment = AgentDeployment::factory()->create([
            'organization_id' => $this->organization->id,
            'status' => 'active',
        ]);

        $result = app(PauseDeploymentAction::class)->execute($deployment);

        $this->assertInstanceOf(AgentDeployment::class, $result);
        $this->assertEquals('paused', $result->status);
    }
}
