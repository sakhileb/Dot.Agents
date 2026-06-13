<?php

namespace Tests\Feature\Actions\Social;

use App\Actions\Social\EscalateConversationAction;
use App\Models\Organization;
use App\Models\SocialAccount;
use App\Models\SocialConversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EscalateConversationActionTest extends TestCase
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
    }

    #[Test]
    public function test_escalates_conversation(): void
    {
        $account = SocialAccount::factory()->create(['organization_id' => $this->organization->id]);
        $conversation = SocialConversation::factory()->create([
            'organization_id' => $this->organization->id,
            'social_account_id' => $account->id,
            'status' => 'open',
            'is_escalated' => false,
        ]);

        $result = app(EscalateConversationAction::class)->execute(
            $conversation,
            escalatedTo: $this->user->id,
            escalatedBy: $this->user->id,
            reason: 'Customer is very upset',
        );

        $this->assertEquals('escalated', $result->status);
        $this->assertTrue((bool) $result->is_escalated);
        $this->assertTrue((bool) $result->requires_human);
        $this->assertEquals('urgent', $result->priority);
        $this->assertNotNull($result->escalated_at);
    }
}
