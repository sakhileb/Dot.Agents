<?php

namespace Tests\Feature\Actions;

use App\Actions\Governance\ProcessApprovalAction;
use App\Events\ApprovalProcessed;
use App\Models\AgentApproval;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class ProcessApprovalActionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $user = User::factory()->create();
        $this->actingAs($user);
        Gate::before(fn () => true); // bypass policy for these tests
    }

    public function test_approves_pending_approval(): void
    {
        Event::fake();
        $approval = AgentApproval::factory()->pending()->create();
        $reviewer = User::factory()->create();
        $this->actingAs($reviewer);

        app(ProcessApprovalAction::class)->execute($approval, 'approved', 'Looks good');

        $this->assertDatabaseHas('agent_approvals', [
            'id' => $approval->id,
            'status' => 'approved',
            'reviewed_by' => $reviewer->id,
            'reviewer_notes' => 'Looks good',
        ]);
    }

    public function test_rejects_pending_approval(): void
    {
        Event::fake();
        $approval = AgentApproval::factory()->pending()->create();
        $reviewer = User::factory()->create();
        $this->actingAs($reviewer);

        app(ProcessApprovalAction::class)->execute($approval, 'rejected', 'Too risky');

        $this->assertDatabaseHas('agent_approvals', [
            'id' => $approval->id,
            'status' => 'rejected',
        ]);
    }

    public function test_fires_approval_processed_event(): void
    {
        Event::fake();
        $approval = AgentApproval::factory()->pending()->create();
        $this->actingAs(User::factory()->create());

        app(ProcessApprovalAction::class)->execute($approval, 'approved');

        Event::assertDispatched(ApprovalProcessed::class, fn ($e) => $e->approval->id === $approval->id);
    }

    public function test_cannot_process_already_processed_approval(): void
    {
        $approval = AgentApproval::factory()->approved()->create();
        $this->actingAs(User::factory()->create());

        $this->expectException(\RuntimeException::class);

        app(ProcessApprovalAction::class)->execute($approval, 'approved');
    }

    public function test_cannot_process_expired_approval(): void
    {
        $approval = AgentApproval::factory()->pending()->create([
            'expires_at' => now()->subMinute(),
        ]);
        $this->actingAs(User::factory()->create());

        $this->expectException(\RuntimeException::class);

        app(ProcessApprovalAction::class)->execute($approval, 'approved');
    }

    public function test_rejected_approval_sets_task_status_to_failed(): void
    {
        Event::fake();
        $approval = AgentApproval::factory()->pending()->create();
        $this->actingAs(User::factory()->create());

        app(ProcessApprovalAction::class)->execute($approval, 'rejected');

        $this->assertDatabaseHas('agent_tasks', [
            'id' => $approval->task_id,
            'status' => 'failed',
        ]);
    }
}
