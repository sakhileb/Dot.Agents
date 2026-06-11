<?php

namespace Tests\Feature\Actions;

use App\Actions\Social\CaptureLeadAction;
use App\Actions\Social\ConnectSocialAccountAction;
use App\Actions\Social\EscalateConversationAction;
use App\Actions\Social\QualifyLeadAction;
use App\Actions\Social\RecordSocialConversionAction;
use App\DTOs\Social\CaptureLeadData;
use App\DTOs\Social\ConnectSocialAccountData;
use App\Events\PurchaseIntentDetected;
use App\Events\SocialConversionAchieved;
use App\Events\SocialLeadCaptured;
use App\Models\Organization;
use App\Models\SocialAccount;
use App\Models\SocialConversation;
use App\Models\SocialLead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class SocialCommerceActionsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->organization = Organization::factory()->create(['owner_id' => $this->user->id]);
        $this->organization->users()->attach($this->user, ['role' => 'owner']);

        $this->actingAs($this->user);
        session(['current_organization_id' => $this->organization->id]);
        Gate::before(fn () => true); // bypass policies in unit tests
    }

    // ── ConnectSocialAccountAction ───────────────────────────────────────────

    public function test_connect_social_account_persists_record(): void
    {
        $data = ConnectSocialAccountData::fromArray([
            'organization_id' => $this->organization->id,
            'connected_by' => $this->user->id,
            'platform' => 'instagram',
            'platform_account_id' => 'ig_123',
            'account_name' => 'My Brand',
            'access_token' => 'tok_abc',
        ]);

        $account = app(ConnectSocialAccountAction::class)->execute($data);

        $this->assertDatabaseHas('social_accounts', [
            'id' => $account->id,
            'organization_id' => $this->organization->id,
            'platform' => 'instagram',
            'platform_account_id' => 'ig_123',
            'status' => 'active',
        ]);
    }

    public function test_connect_social_account_encrypts_token(): void
    {
        $data = ConnectSocialAccountData::fromArray([
            'organization_id' => $this->organization->id,
            'connected_by' => $this->user->id,
            'platform' => 'facebook',
            'platform_account_id' => 'fb_456',
            'account_name' => 'FB Page',
            'access_token' => 'super_secret_token',
        ]);

        $account = app(ConnectSocialAccountAction::class)->execute($data);

        // Verify the token is not stored as plaintext
        $raw = \DB::table('social_accounts')->where('id', $account->id)->value('access_token');
        $this->assertNotEquals('super_secret_token', $raw);
        $this->assertNotEmpty($raw);
    }

    // ── CaptureLeadAction ────────────────────────────────────────────────────

    public function test_capture_lead_persists_and_fires_event(): void
    {
        Event::fake();

        $data = CaptureLeadData::fromArray([
            'organization_id' => $this->organization->id,
            'platform' => 'instagram',
            'contact_platform_id' => 'ig_user_999',
            'contact_name' => 'Jane Prospect',
            'intent_level' => 'interested',
            'lead_score' => 45.0,
            'intent_score' => 35.0,
        ]);

        $lead = app(CaptureLeadAction::class)->execute($data);

        $this->assertDatabaseHas('social_leads', [
            'id' => $lead->id,
            'organization_id' => $this->organization->id,
            'platform' => 'instagram',
            'contact_platform_id' => 'ig_user_999',
            'status' => 'new',
        ]);

        Event::assertDispatched(SocialLeadCaptured::class, fn ($e) => $e->lead->id === $lead->id);
    }

    public function test_capture_lead_fires_purchase_intent_event_for_high_intent(): void
    {
        Event::fake();

        $conversation = SocialConversation::factory()->create([
            'organization_id' => $this->organization->id,
            'platform' => 'whatsapp',
            'contact_platform_id' => 'wa_777',
            'channel_type' => 'dm',
        ]);

        $data = CaptureLeadData::fromArray([
            'organization_id' => $this->organization->id,
            'platform' => 'whatsapp',
            'contact_platform_id' => 'wa_888',
            'social_conversation_id' => $conversation->id,
            'intent_level' => 'high_intent',
            'lead_score' => 90.0,
            'intent_score' => 92.0,
        ]);

        app(CaptureLeadAction::class)->execute($data);

        Event::assertDispatched(PurchaseIntentDetected::class);
    }

    public function test_capture_lead_deduplicates_by_platform_contact(): void
    {
        Event::fake();

        $sharedData = [
            'organization_id' => $this->organization->id,
            'platform' => 'facebook',
            'contact_platform_id' => 'fb_dedup_001',
            'intent_level' => 'browsing',
            'lead_score' => 10.0,
            'intent_score' => 10.0,
        ];

        $lead1 = app(CaptureLeadAction::class)->execute(CaptureLeadData::fromArray($sharedData));

        $sharedData['lead_score'] = 55.0;
        $sharedData['intent_score'] = 55.0;
        $sharedData['intent_level'] = 'considering';

        $lead2 = app(CaptureLeadAction::class)->execute(CaptureLeadData::fromArray($sharedData));

        // Same record updated — not a duplicate
        $this->assertEquals($lead1->id, $lead2->id);
        $this->assertDatabaseCount('social_leads', 1);
    }

    // ── QualifyLeadAction ────────────────────────────────────────────────────

    public function test_qualify_lead_sets_status_and_recommended_actions(): void
    {
        $lead = SocialLead::factory()->create([
            'organization_id' => $this->organization->id,
            'status' => 'new',
            'lead_score' => 85.0,
            'intent_score' => 88.0,
            'intent_level' => 'browsing',
        ]);

        $qualified = app(QualifyLeadAction::class)->execute($lead, $this->user->id);

        $this->assertEquals('qualified', $qualified->status);
        $this->assertEquals('ready_to_buy', $qualified->intent_level);
        $this->assertEquals('hot', $qualified->priority);
        $this->assertNotEmpty($qualified->recommended_actions);
        $this->assertNotNull($qualified->qualified_at);
    }

    // ── EscalateConversationAction ────────────────────────────────────────────

    public function test_escalate_conversation_sets_flags_and_assigns_user(): void
    {
        $conversation = SocialConversation::factory()->create([
            'organization_id' => $this->organization->id,
            'platform' => 'facebook',
            'contact_platform_id' => 'fb_esc_001',
            'channel_type' => 'dm',
            'status' => 'open',
            'sentiment' => 'angry',
        ]);

        $escalated = app(EscalateConversationAction::class)->execute(
            conversation: $conversation,
            escalatedTo: $this->user->id,
            escalatedBy: $this->user->id,
            reason: 'Customer very angry',
        );

        $this->assertEquals('escalated', $escalated->status);
        $this->assertTrue($escalated->is_escalated);
        $this->assertTrue($escalated->requires_human);
        $this->assertEquals($this->user->id, $escalated->escalated_to);
        $this->assertEquals('urgent', $escalated->priority);
    }

    // ── RecordSocialConversionAction ──────────────────────────────────────────

    public function test_record_conversion_fires_event_and_marks_lead_converted(): void
    {
        Event::fake();

        $lead = SocialLead::factory()->create([
            'organization_id' => $this->organization->id,
            'platform' => 'linkedin',
            'contact_platform_id' => 'li_conv_001',
            'status' => 'qualified',
        ]);

        $conversion = app(RecordSocialConversionAction::class)->execute(
            organizationId: $this->organization->id,
            conversionType: 'purchase',
            actorId: $this->user->id,
            socialLeadId: $lead->id,
            revenue: 1500.00,
            agentAttributionScore: 75.0,
        );

        $this->assertDatabaseHas('social_conversions', [
            'id' => $conversion->id,
            'conversion_type' => 'purchase',
        ]);

        $this->assertDatabaseHas('social_leads', [
            'id' => $lead->id,
            'status' => 'converted',
        ]);

        Event::assertDispatched(SocialConversionAchieved::class);
    }

    // ── Tenant isolation ─────────────────────────────────────────────────────

    public function test_social_account_cannot_be_accessed_across_organizations(): void
    {
        $otherOrg = Organization::factory()->create();
        $otherUser = User::factory()->create();
        $otherOrg->users()->attach($otherUser, ['role' => 'owner']);

        $account = SocialAccount::factory()->create([
            'organization_id' => $otherOrg->id,
            'platform' => 'x',
            'platform_account_id' => 'x_tenant_test',
            'account_name' => 'Other Org Account',
            'status' => 'active',
        ]);

        // Current org session should NOT see other org's accounts
        $visible = SocialAccount::all();
        $this->assertFalse($visible->contains('id', $account->id));
    }
}
