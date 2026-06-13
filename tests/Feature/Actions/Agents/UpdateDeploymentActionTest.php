<?php

namespace Tests\Feature\Actions\Agents;

use App\Actions\Agents\UpdateDeploymentAction;
use App\DTOs\Agents\UpdateDeploymentData;
use App\Events\AgentUpdated;
use App\Models\AgentDeployment;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateDeploymentActionTest extends TestCase
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
    public function test_updates_allowed_fields(): void
    {
        Event::fake([AgentUpdated::class]);

        $deployment = AgentDeployment::factory()->create([
            'organization_id' => $this->organization->id,
            'deployment_mode' => 'advisory',
            'confidence_threshold' => 75.0,
        ]);

        $data = new UpdateDeploymentData(
            name: 'Updated Name',
            deploymentMode: 'semi-autonomous',
            confidenceThreshold: 85.0,
        );

        $result = app(UpdateDeploymentAction::class)->execute($deployment, $data);

        $this->assertEquals('semi-autonomous', $result->deployment_mode);
        $this->assertEquals(85.0, $result->confidence_threshold);
        Event::assertDispatched(AgentUpdated::class);
    }

    #[Test]
    public function test_returns_refreshed_deployment(): void
    {
        Event::fake();

        $deployment = AgentDeployment::factory()->create([
            'organization_id' => $this->organization->id,
        ]);

        $data = new UpdateDeploymentData(customInstructions: 'Be concise.');

        $result = app(UpdateDeploymentAction::class)->execute($deployment, $data);

        $this->assertInstanceOf(AgentDeployment::class, $result);
    }
}
