<?php

namespace Tests\Feature\Actions\Social;

use App\Actions\Social\DisconnectSocialAccountAction;
use App\Actions\Social\MarkEscalationHandledAction;
use App\DTOs\Social\DisconnectSocialAccountData;
use App\DTOs\Social\MarkEscalationHandledData;
use App\Models\Organization;
use App\Models\SocialAccount;
use App\Models\SocialSentimentScore;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class SocialEngagementActionsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Organization $organization;

    private SocialAccount $socialAccount;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->organization = Organization::factory()->create(['owner_id' => $this->user->id]);
        $this->socialAccount = SocialAccount::factory()->create([
            'organization_id' => $this->organization->id,
            'platform' => 'instagram',
        ]);
        $this->actingAs($this->user);
        Gate::before(fn () => true);
        session(['current_organization_id' => $this->organization->id]);
    }

    // ─── MarkEscalationHandledAction ──────────────────────────────────────────

    public function test_mark_escalation_handled_sets_flag_to_true(): void
    {
        $score = SocialSentimentScore::create([
            'organization_id' => $this->organization->id,
            'social_account_id' => $this->socialAccount->id,
            'platform' => 'instagram',
            'sentiment' => 'negative',
            'score' => 25.0,
            'confidence' => 80.0,
            'subject_type' => 'conversation',
            'requires_escalation' => true,
            'escalation_handled' => false,
            'scored_at' => now(),
        ]);

        $data = MarkEscalationHandledData::from($this->organization->id, $score->id);

        app(MarkEscalationHandledAction::class)->execute($data);

        $this->assertDatabaseHas('social_sentiment_scores', [
            'id' => $score->id,
            'escalation_handled' => true,
        ]);
    }

    // ─── DisconnectSocialAccountAction ────────────────────────────────────────

    public function test_execute_for_platform_disconnects_all_matching_accounts(): void
    {
        // Create a second account on same platform
        $account2 = SocialAccount::factory()->create([
            'organization_id' => $this->organization->id,
            'platform' => 'instagram',
        ]);

        // Unrelated platform — should not be deleted
        $otherAccount = SocialAccount::factory()->create([
            'organization_id' => $this->organization->id,
            'platform' => 'linkedin',
        ]);

        $data = DisconnectSocialAccountData::fromPlatform('instagram');

        $count = app(DisconnectSocialAccountAction::class)->executeForPlatform($this->organization, $data);

        $this->assertEquals(2, $count);
        $this->assertSoftDeleted('social_accounts', ['id' => $this->socialAccount->id]);
        $this->assertSoftDeleted('social_accounts', ['id' => $account2->id]);
        // Unrelated account untouched
        $this->assertDatabaseHas('social_accounts', ['id' => $otherAccount->id, 'deleted_at' => null]);
    }

    public function test_execute_single_soft_deletes_the_account(): void
    {
        app(DisconnectSocialAccountAction::class)->executeSingle($this->socialAccount);

        $this->assertSoftDeleted('social_accounts', ['id' => $this->socialAccount->id]);
    }
}
