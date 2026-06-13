<?php

namespace Tests\Feature\Actions\Social;

use App\Actions\Social\ApproveSocialPostAction;
use App\Models\Organization;
use App\Models\SocialAccount;
use App\Models\SocialPage;
use App\Models\SocialPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ApproveSocialPostActionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Organization $organization;

    private SocialPost $post;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->organization = Organization::factory()->create(['owner_id' => $this->user->id]);
        $account = SocialAccount::factory()->create(['organization_id' => $this->organization->id]);
        $page = SocialPage::create([
            'uuid' => (string) Str::uuid(),
            'organization_id' => $this->organization->id,
            'social_account_id' => $account->id,
            'platform_page_id' => 'page_'.uniqid(),
            'name' => 'Test Page',
            'is_active' => true,
        ]);
        $this->post = SocialPost::create([
            'uuid' => (string) Str::uuid(),
            'organization_id' => $this->organization->id,
            'social_page_id' => $page->id,
            'content' => 'Pending post',
            'post_type' => 'post',
            'status' => 'draft',
            'approval_status' => 'pending',
        ]);
        $this->actingAs($this->user);
        Gate::before(fn () => true);
    }

    #[Test]
    public function test_approves_post(): void
    {
        $result = app(ApproveSocialPostAction::class)->execute($this->post, $this->user->id);

        $this->assertEquals('approved', $result->approval_status);
        $this->assertEquals($this->user->id, $result->approved_by);
        $this->assertNotNull($result->approved_at);
    }
}
