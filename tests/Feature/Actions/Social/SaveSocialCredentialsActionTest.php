<?php

namespace Tests\Feature\Actions\Social;

use App\Actions\Social\SaveSocialCredentialsAction;
use App\Models\Organization;
use App\Models\OrganizationSocialCredential;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SaveSocialCredentialsActionTest extends TestCase
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
    public function test_creates_credentials(): void
    {
        $result = app(SaveSocialCredentialsAction::class)->execute(
            $this->organization,
            'facebook',
            ['client_id' => 'abc123', 'client_secret' => 'secret456'],
            $this->user->id,
        );

        $this->assertInstanceOf(OrganizationSocialCredential::class, $result);
        $this->assertEquals('facebook', $result->platform);
        $this->assertEquals($this->organization->id, $result->organization_id);
    }

    #[Test]
    public function test_updates_existing_credentials(): void
    {
        OrganizationSocialCredential::factory()->create([
            'organization_id' => $this->organization->id,
            'platform' => 'facebook',
            'client_id' => 'old_id',
        ]);

        app(SaveSocialCredentialsAction::class)->execute(
            $this->organization,
            'facebook',
            ['client_id' => 'new_id', 'client_secret' => 'new_secret'],
            $this->user->id,
        );

        $this->assertCount(1, OrganizationSocialCredential::where('organization_id', $this->organization->id)->get());
        $cred = OrganizationSocialCredential::where('organization_id', $this->organization->id)->first();
        $this->assertEquals('new_id', $cred->client_id);
    }

    #[Test]
    public function test_delete_removes_credentials(): void
    {
        OrganizationSocialCredential::factory()->create([
            'organization_id' => $this->organization->id,
            'platform' => 'instagram',
            'client_id' => 'ig_id',
        ]);

        app(SaveSocialCredentialsAction::class)->delete($this->organization, 'instagram');

        $this->assertDatabaseMissing('organization_social_credentials', [
            'organization_id' => $this->organization->id,
            'platform' => 'instagram',
        ]);
    }
}
