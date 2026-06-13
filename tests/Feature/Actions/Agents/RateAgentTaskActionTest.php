<?php

namespace Tests\Feature\Actions\Agents;

use App\Actions\Agents\RateAgentTaskAction;
use App\Events\AgentTaskRated;
use App\Models\AgentDeployment;
use App\Models\AgentTask;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RateAgentTaskActionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Organization $organization;

    private AgentTask $task;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->organization = Organization::factory()->create(['owner_id' => $this->user->id]);
        $deployment = AgentDeployment::factory()->create(['organization_id' => $this->organization->id]);
        $this->task = AgentTask::factory()->create([
            'organization_id' => $this->organization->id,
            'agent_deployment_id' => $deployment->id,
            'status' => 'completed',
            'rated_at' => null,
        ]);
        $this->actingAs($this->user);
        Gate::before(fn () => true);
        session(['current_organization_id' => $this->organization->id]);
    }

    #[Test]
    public function test_records_rating_on_task(): void
    {
        Event::fake([AgentTaskRated::class]);

        $result = app(RateAgentTaskAction::class)->execute($this->task, 5, 'Excellent work');

        $this->assertEquals(5, $result->user_rating);
        $this->assertEquals('Excellent work', $result->user_feedback);
        $this->assertNotNull($result->rated_at);
        Event::assertDispatched(AgentTaskRated::class);
    }

    #[Test]
    public function test_throws_on_invalid_rating_below_one(): void
    {
        $this->expectException(ValidationException::class);
        app(RateAgentTaskAction::class)->execute($this->task, 0);
    }

    #[Test]
    public function test_throws_on_invalid_rating_above_five(): void
    {
        $this->expectException(ValidationException::class);
        app(RateAgentTaskAction::class)->execute($this->task, 6);
    }

    #[Test]
    public function test_throws_when_already_rated(): void
    {
        $this->task->update(['rated_at' => now()]);

        $this->expectException(ValidationException::class);
        app(RateAgentTaskAction::class)->execute($this->task, 4);
    }
}
