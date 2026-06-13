<?php

namespace Tests\Feature\Actions\Social;

use App\Actions\Social\ConnectSocialAccountAction;
use App\DTOs\Social\ConnectSocialAccountData;
use App\Models\Organization;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ConnectSocialAccountActionTest extends TestCase
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
    public function test_creates_social_account(): void
    {
        $data = new ConnectSocialAccountData(
            organizationId: $this->organization->id,
            connectedBy: $this->user->id,
            platform: 'facebook',
            platformAccountId: 'fb_123',
            accountName: 'Acme Corp',
            accessToken: 'token_abc',
            accountHandle: '@acme',
            accountType: 'page',
        );

        $result = app(ConnectSocialAccountAction::class)->execute($data);

        $this->assertInstanceOf(SocialAccount::class, $result);
        $this->assertEquals('facebook', $result->platform);
        $this->assertEquals($this->organization->id, $result->organization_id);
        $this->assertEquals('active', $result->status);
        $this->assertDatabaseHas('social_accounts', ['platform_account_id' => 'fb_123']);
    }
}
