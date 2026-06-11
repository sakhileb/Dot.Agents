<?php

namespace Tests\Feature\Security;

use App\Http\Middleware\ConsentRequiredMiddleware;
use App\Http\Middleware\OrganizationContextMiddleware;
use App\Models\Organization;
use App\Models\SocialAccount;
use App\Models\SocialConversation;
use App\Models\SocialLead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SCCS Tenant Isolation Test Suite
 *
 * Verifies that the organization scope prevents data leakage
 * between tenants across all social commerce models.
 */
class SocialCommerceTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Organization $orgA;

    private Organization $orgB;

    private User $userA;

    private User $userB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->orgA = Organization::factory()->create();
        $this->orgB = Organization::factory()->create();

        $this->userA = User::factory()->create();
        $this->userB = User::factory()->create();

        $this->orgA->users()->attach($this->userA, ['role' => 'owner']);
        $this->orgB->users()->attach($this->userB, ['role' => 'owner']);
    }

    public function test_social_accounts_scoped_to_current_organization(): void
    {
        SocialAccount::factory()->create(['organization_id' => $this->orgA->id, 'platform' => 'facebook', 'platform_account_id' => 'fa1']);
        SocialAccount::factory()->create(['organization_id' => $this->orgB->id, 'platform' => 'instagram', 'platform_account_id' => 'ia1']);

        $this->actingAs($this->userA);
        session(['current_organization_id' => $this->orgA->id]);

        $accounts = SocialAccount::all();

        $this->assertCount(1, $accounts);
        $this->assertEquals($this->orgA->id, $accounts->first()->organization_id);
    }

    public function test_social_leads_scoped_to_current_organization(): void
    {
        SocialLead::factory()->create(['organization_id' => $this->orgA->id, 'platform' => 'linkedin', 'contact_platform_id' => 'la1']);
        SocialLead::factory()->create(['organization_id' => $this->orgB->id, 'platform' => 'linkedin', 'contact_platform_id' => 'lb1']);

        $this->actingAs($this->userA);
        session(['current_organization_id' => $this->orgA->id]);

        $leads = SocialLead::all();

        $this->assertCount(1, $leads);
        $this->assertEquals($this->orgA->id, $leads->first()->organization_id);
    }

    public function test_social_conversations_scoped_to_current_organization(): void
    {
        SocialConversation::factory()->create([
            'organization_id' => $this->orgA->id,
            'platform' => 'whatsapp',
            'contact_platform_id' => 'wa_a',
            'channel_type' => 'dm',
        ]);
        SocialConversation::factory()->create([
            'organization_id' => $this->orgB->id,
            'platform' => 'whatsapp',
            'contact_platform_id' => 'wa_b',
            'channel_type' => 'dm',
        ]);

        $this->actingAs($this->userA);
        session(['current_organization_id' => $this->orgA->id]);

        $conversations = SocialConversation::all();

        $this->assertCount(1, $conversations);
        $this->assertEquals($this->orgA->id, $conversations->first()->organization_id);
    }

    public function test_scope_bypassed_without_session(): void
    {
        SocialAccount::factory()->create(['organization_id' => $this->orgA->id, 'platform' => 'x', 'platform_account_id' => 'xa1']);
        SocialAccount::factory()->create(['organization_id' => $this->orgB->id, 'platform' => 'x', 'platform_account_id' => 'xb1']);

        // No session set — background job context
        $accounts = SocialAccount::withoutGlobalScope('organization')->get();

        $this->assertCount(2, $accounts);
    }

    public function test_unauthenticated_user_cannot_access_social_routes(): void
    {
        $response = $this->get(route('social.dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_access_social_dashboard(): void
    {
        $this->actingAs($this->userA);
        session(['current_organization_id' => $this->orgA->id]);

        $response = $this->withoutMiddleware([
            ConsentRequiredMiddleware::class,
            OrganizationContextMiddleware::class,
        ])->get(route('social.dashboard'));

        $response->assertOk();
    }
}
