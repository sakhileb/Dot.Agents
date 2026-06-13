<?php

namespace Tests\Feature\Actions\Social;

use App\Actions\Social\ScheduleSocialPostAction;
use App\DTOs\Social\SocialPostData;
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

class ScheduleSocialPostActionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Organization $organization;

    private int $pageId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->organization = Organization::factory()->create(['owner_id' => $this->user->id]);

        // Create a minimal social page record
        $this->pageId = SocialPage::create([
            'uuid' => Str::uuid(),
            'organization_id' => $this->organization->id,
            'social_account_id' => SocialAccount::factory()->create(['organization_id' => $this->organization->id])->id,
            'platform_page_id' => 'page_'.uniqid(),
            'name' => 'Test Page',
            'is_active' => true,
        ])->id;

        $this->actingAs($this->user);
        Gate::before(fn () => true);
    }

    #[Test]
    public function test_creates_post_requiring_approval_as_draft(): void
    {
        $data = new SocialPostData(
            organizationId: $this->organization->id,
            socialPageId: $this->pageId,
            content: 'Check out our new product!',
            requiresApproval: true,
        );

        $result = app(ScheduleSocialPostAction::class)->execute($data, $this->user->id);

        $this->assertInstanceOf(SocialPost::class, $result);
        $this->assertEquals('draft', $result->status);
        $this->assertEquals('pending', $result->approval_status);
    }

    #[Test]
    public function test_creates_scheduled_post_without_approval(): void
    {
        $data = new SocialPostData(
            organizationId: $this->organization->id,
            socialPageId: $this->pageId,
            content: 'Scheduled announcement',
            requiresApproval: false,
            scheduledAt: now()->addDay(),
        );

        $result = app(ScheduleSocialPostAction::class)->execute($data, $this->user->id);

        $this->assertEquals('scheduled', $result->status);
        $this->assertEquals('approved', $result->approval_status);
    }
}
