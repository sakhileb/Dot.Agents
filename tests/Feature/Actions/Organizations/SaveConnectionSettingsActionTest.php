<?php

namespace Tests\Feature\Actions\Organizations;

use App\Actions\Organizations\SaveConnectionSettingsAction;
use App\DTOs\Organizations\SaveConnectionSettingsData;
use App\Models\Organization;
use App\Models\SocialAccount;
use App\Models\SocialConnectionSettings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SaveConnectionSettingsActionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Organization $organization;

    private SocialAccount $account;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->organization = Organization::factory()->create(['owner_id' => $this->user->id]);
        $this->account = SocialAccount::factory()->create(['organization_id' => $this->organization->id]);
        $this->actingAs($this->user);
        Gate::before(fn () => true);
        session(['current_organization_id' => $this->organization->id]);
    }

    #[Test]
    public function test_creates_connection_settings(): void
    {
        $data = new SaveConnectionSettingsData(
            goals: ['increase_engagement'],
            aiFeatures: ['auto_reply'],
            permissions: ['read', 'write'],
            autonomyLevel: 2,
        );

        $result = app(SaveConnectionSettingsAction::class)->execute($this->account, $data);

        $this->assertInstanceOf(SocialConnectionSettings::class, $result);
        $this->assertEquals($this->account->id, $result->social_account_id);
        $this->assertEquals(2, $result->autonomy_level);
    }

    #[Test]
    public function test_updates_existing_settings(): void
    {
        $first = new SaveConnectionSettingsData(autonomyLevel: 1);
        app(SaveConnectionSettingsAction::class)->execute($this->account, $first);

        $second = new SaveConnectionSettingsData(autonomyLevel: 3);
        $result = app(SaveConnectionSettingsAction::class)->execute($this->account, $second);

        $this->assertEquals(3, $result->autonomy_level);
        $this->assertCount(1, SocialConnectionSettings::where('social_account_id', $this->account->id)->get());
    }
}
