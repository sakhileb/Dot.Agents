<?php

namespace Tests\Feature\Actions\Social;

use App\Actions\Social\DisconnectSocialAccountAction;
use App\DTOs\Social\DisconnectSocialAccountData;
use App\Models\Organization;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DisconnectSocialAccountActionTest extends TestCase
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
    public function test_disconnects_all_accounts_for_platform(): void
    {
        SocialAccount::factory()->count(2)->create([
            'organization_id' => $this->organization->id,
            'platform' => 'facebook',
            'status' => 'active',
        ]);

        $data = DisconnectSocialAccountData::fromPlatform('facebook');
        $count = app(DisconnectSocialAccountAction::class)->executeForPlatform($this->organization, $data);

        $this->assertEquals(2, $count);
        $this->assertCount(0, SocialAccount::where('organization_id', $this->organization->id)
            ->where('platform', 'facebook')->get());
    }

    #[Test]
    public function test_disconnects_single_account(): void
    {
        $account = SocialAccount::factory()->create([
            'organization_id' => $this->organization->id,
            'platform' => 'linkedin',
        ]);

        app(DisconnectSocialAccountAction::class)->executeSingle($account);

        $this->assertSoftDeleted('social_accounts', ['id' => $account->id]);
    }
}
