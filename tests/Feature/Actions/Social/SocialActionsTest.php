<?php

namespace Tests\Feature\Actions\Social;

use App\Actions\Social\ApproveSocialPostAction;
use App\Actions\Social\CaptureLeadAction;
use App\Actions\Social\ConnectSocialAccountAction;
use App\Actions\Social\EscalateConversationAction;
use App\Actions\Social\QualifyLeadAction;
use App\Actions\Social\RecordSocialConversionAction;
use App\Actions\Social\SaveSocialCredentialsAction;
use App\Actions\Social\ScheduleSocialPostAction;
use App\DTOs\Social\CaptureLeadData;
use App\DTOs\Social\ConnectSocialAccountData;
use App\DTOs\Social\SocialPostData;
use App\Events\SocialConversionAchieved;
use App\Events\SocialLeadCaptured;
use App\Models\Organization;
use App\Models\OrganizationSocialCredential;
use App\Models\SocialAccount;
use App\Models\SocialConversation;
use App\Models\SocialLead;
use App\Models\SocialPage;
use App\Models\SocialPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Tests\TestCase;

class SocialActionsTest extends TestCase
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
        ]);
        $this->actingAs($this->user);
        Gate::before(fn () => true);
        session(['current_organization_id' => $this->organization->id]);
    }

    // ─── ConnectSocialAccountAction ───────────────────────────────────────────

    public function test_connect_social_account_creates_record(): void
    {
        $data = new ConnectSocialAccountData(
            organizationId: $this->organization->id,
            connectedBy: $this->user->id,
            platform: 'linkedin',
            platformAccountId: 'li_12345',
            accountName: 'Acme LinkedIn',
            accessToken: 'tok_abc123',
        );

        $account = app(ConnectSocialAccountAction::class)->execute($data);

        $this->assertDatabaseHas('social_accounts', [
            'organization_id' => $this->organization->id,
            'platform' => 'linkedin',
            'platform_account_id' => 'li_12345',
            'status' => 'active',
        ]);
        $this->assertEquals('linkedin', $account->platform);
    }

    public function test_connect_social_account_logs_audit(): void
    {
        $data = new ConnectSocialAccountData(
            organizationId: $this->organization->id,
            connectedBy: $this->user->id,
            platform: 'instagram',
            platformAccountId: 'ig_99',
            accountName: 'Acme IG',
            accessToken: 'tok_xyz',
        );

        app(ConnectSocialAccountAction::class)->execute($data);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'social_account.connected',
            'organization_id' => $this->organization->id,
        ]);
    }

    // ─── SaveSocialCredentialsAction ──────────────────────────────────────────

    public function test_save_social_credentials_upserts_record(): void
    {
        $cred = app(SaveSocialCredentialsAction::class)->execute(
            $this->organization,
            'facebook',
            ['client_id' => 'fb_client', 'client_secret' => 'fb_secret'],
            $this->user->id,
        );

        $this->assertDatabaseHas('organization_social_credentials', [
            'organization_id' => $this->organization->id,
            'platform' => 'facebook',
        ]);
        $this->assertEquals('facebook', $cred->platform);
        $this->assertEquals('fb_client', $cred->client_id);
    }

    public function test_save_social_credentials_updates_existing(): void
    {
        app(SaveSocialCredentialsAction::class)->execute(
            $this->organization,
            'twitter',
            ['client_id' => 'old_client', 'client_secret' => 'old_secret'],
            $this->user->id,
        );

        app(SaveSocialCredentialsAction::class)->execute(
            $this->organization,
            'twitter',
            ['client_id' => 'new_client', 'client_secret' => 'new_secret'],
            $this->user->id,
        );

        $this->assertDatabaseCount('organization_social_credentials', 1);
        $updated = OrganizationSocialCredential::where('platform', 'twitter')->first();
        $this->assertEquals('new_client', $updated->client_id);
    }

    // ─── CaptureLeadAction ────────────────────────────────────────────────────

    public function test_capture_lead_creates_new_lead(): void
    {
        Event::fake([SocialLeadCaptured::class]);

        $data = new CaptureLeadData(
            organizationId: $this->organization->id,
            platform: 'facebook',
            contactPlatformId: 'fb_contact_001',
            contactName: 'Jane Prospect',
            intentLevel: 'interested',
            leadScore: 55.0,
            intentScore: 40.0,
        );

        $lead = app(CaptureLeadAction::class)->execute($data);

        $this->assertDatabaseHas('social_leads', [
            'organization_id' => $this->organization->id,
            'platform' => 'facebook',
            'contact_platform_id' => 'fb_contact_001',
            'status' => 'new',
        ]);
        Event::assertDispatched(SocialLeadCaptured::class);
        $this->assertEquals('new', $lead->status);
    }

    public function test_capture_lead_updates_existing_lead(): void
    {
        Event::fake([SocialLeadCaptured::class]);

        SocialLead::factory()->create([
            'organization_id' => $this->organization->id,
            'platform' => 'facebook',
            'contact_platform_id' => 'fb_existing_001',
            'lead_score' => 30.0,
            'intent_score' => 20.0,
        ]);

        $data = new CaptureLeadData(
            organizationId: $this->organization->id,
            platform: 'facebook',
            contactPlatformId: 'fb_existing_001',
            intentLevel: 'considering',
            leadScore: 60.0,
            intentScore: 55.0,
        );

        app(CaptureLeadAction::class)->execute($data);

        // Should not fire SocialLeadCaptured for existing lead
        Event::assertNotDispatched(SocialLeadCaptured::class);
        // Lead score should be updated to the higher value
        $this->assertDatabaseHas('social_leads', [
            'contact_platform_id' => 'fb_existing_001',
        ]);
    }

    // ─── QualifyLeadAction ────────────────────────────────────────────────────

    public function test_qualify_lead_sets_qualified_status(): void
    {
        $lead = SocialLead::factory()->create([
            'organization_id' => $this->organization->id,
            'intent_score' => 80.0,
            'lead_score' => 70.0,
        ]);

        $result = app(QualifyLeadAction::class)->execute($lead, $this->user->id);

        $this->assertEquals('qualified', $result->status);
        $this->assertNotNull($result->qualified_at);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'social_lead.qualified',
            'organization_id' => $this->organization->id,
        ]);
    }

    public function test_qualify_lead_resolves_intent_level_from_score(): void
    {
        $highIntentLead = SocialLead::factory()->create([
            'organization_id' => $this->organization->id,
            'intent_score' => 92.0,
            'lead_score' => 85.0,
        ]);

        $result = app(QualifyLeadAction::class)->execute($highIntentLead, $this->user->id);

        $this->assertEquals('high_intent', $result->intent_level);
        $this->assertEquals('hot', $result->priority);
    }

    // ─── RecordSocialConversionAction ─────────────────────────────────────────

    public function test_record_conversion_creates_record_and_fires_event(): void
    {
        Event::fake([SocialConversionAchieved::class]);

        $conversion = app(RecordSocialConversionAction::class)->execute(
            organizationId: $this->organization->id,
            conversionType: 'demo_booked',
            actorId: $this->user->id,
            revenue: 500.00,
        );

        $this->assertDatabaseHas('social_conversions', [
            'organization_id' => $this->organization->id,
            'conversion_type' => 'demo_booked',
        ]);
        Event::assertDispatched(SocialConversionAchieved::class);
        $this->assertEquals('demo_booked', $conversion->conversion_type);
    }

    public function test_record_conversion_marks_lead_as_converted(): void
    {
        Event::fake([SocialConversionAchieved::class]);

        $lead = SocialLead::factory()->create([
            'organization_id' => $this->organization->id,
            'status' => 'qualified',
        ]);

        app(RecordSocialConversionAction::class)->execute(
            organizationId: $this->organization->id,
            conversionType: 'purchase',
            actorId: $this->user->id,
            socialLeadId: $lead->id,
        );

        $this->assertDatabaseHas('social_leads', [
            'id' => $lead->id,
            'status' => 'converted',
        ]);
    }

    // ─── EscalateConversationAction ───────────────────────────────────────────

    public function test_escalate_conversation_sets_escalated_status(): void
    {
        $conversation = SocialConversation::factory()->create([
            'organization_id' => $this->organization->id,
            'social_account_id' => $this->socialAccount->id,
            'status' => 'open',
        ]);

        $result = app(EscalateConversationAction::class)->execute(
            $conversation,
            escalatedTo: $this->user->id,
            escalatedBy: $this->user->id,
            reason: 'Customer unhappy',
        );

        $this->assertEquals('escalated', $result->status);
        $this->assertTrue((bool) $result->is_escalated);
        $this->assertEquals('urgent', $result->priority);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'social_conversation.escalated',
            'organization_id' => $this->organization->id,
        ]);
    }

    // ─── ScheduleSocialPostAction ─────────────────────────────────────────────

    public function test_schedule_social_post_creates_draft_requiring_approval(): void
    {
        $page = SocialPage::create([
            'uuid' => (string) Str::uuid(),
            'organization_id' => $this->organization->id,
            'social_account_id' => $this->socialAccount->id,
            'platform_page_id' => 'pg_001',
            'name' => 'Test Page',
            'is_active' => true,
        ]);

        $data = new SocialPostData(
            organizationId: $this->organization->id,
            socialPageId: $page->id,
            content: 'Hello World post!',
            requiresApproval: true,
        );

        $post = app(ScheduleSocialPostAction::class)->execute($data, $this->user->id);

        $this->assertDatabaseHas('social_posts', [
            'organization_id' => $this->organization->id,
            'status' => 'draft',
            'approval_status' => 'pending',
        ]);
        $this->assertEquals('pending', $post->approval_status);
    }

    // ─── ApproveSocialPostAction ───────────────────────────────────────────────

    public function test_approve_social_post_sets_approved_status(): void
    {
        $page = SocialPage::create([
            'uuid' => (string) Str::uuid(),
            'organization_id' => $this->organization->id,
            'social_account_id' => $this->socialAccount->id,
            'platform_page_id' => 'pg_002',
            'name' => 'Test Page 2',
            'is_active' => true,
        ]);

        $post = SocialPost::create([
            'uuid' => (string) Str::uuid(),
            'organization_id' => $this->organization->id,
            'social_page_id' => $page->id,
            'post_type' => 'post',
            'content' => 'Awaiting approval',
            'status' => 'draft',
            'approval_status' => 'pending',
        ]);

        $result = app(ApproveSocialPostAction::class)->execute($post, $this->user->id);

        $this->assertEquals('approved', $result->approval_status);
        $this->assertEquals($this->user->id, $result->approved_by);
        $this->assertNotNull($result->approved_at);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'social_post.approved',
            'organization_id' => $this->organization->id,
        ]);
    }
}
