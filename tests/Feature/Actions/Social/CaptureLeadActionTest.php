<?php

namespace Tests\Feature\Actions\Social;

use App\Actions\Social\CaptureLeadAction;
use App\DTOs\Social\CaptureLeadData;
use App\Events\SocialLeadCaptured;
use App\Models\Organization;
use App\Models\SocialLead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CaptureLeadActionTest extends TestCase
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
    }

    #[Test]
    public function test_creates_new_lead(): void
    {
        Event::fake([SocialLeadCaptured::class]);

        $data = new CaptureLeadData(
            organizationId: $this->organization->id,
            platform: 'facebook',
            contactPlatformId: 'fb_user_123',
            contactName: 'Jane Smith',
            intentLevel: 'interested',
            leadScore: 55.0,
            intentScore: 40.0,
        );

        $lead = app(CaptureLeadAction::class)->execute($data);

        $this->assertInstanceOf(SocialLead::class, $lead);
        $this->assertEquals('new', $lead->status);
        $this->assertEquals('awareness', $lead->stage);
        $this->assertDatabaseHas('social_leads', ['contact_platform_id' => 'fb_user_123']);
        Event::assertDispatched(SocialLeadCaptured::class);
    }

    #[Test]
    public function test_updates_existing_lead_without_duplicate(): void
    {
        Event::fake();

        $existing = SocialLead::factory()->create([
            'organization_id' => $this->organization->id,
            'platform' => 'facebook',
            'contact_platform_id' => 'fb_user_999',
            'lead_score' => 30.0,
        ]);

        $data = new CaptureLeadData(
            organizationId: $this->organization->id,
            platform: 'facebook',
            contactPlatformId: 'fb_user_999',
            intentLevel: 'considering',
            leadScore: 70.0,
            intentScore: 55.0,
        );

        app(CaptureLeadAction::class)->execute($data);

        $this->assertCount(1, SocialLead::where('contact_platform_id', 'fb_user_999')->get());
        $this->assertEquals(70.0, $existing->fresh()->lead_score);
    }
}
