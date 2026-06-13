<?php

namespace Tests\Feature\Actions\Social;

use App\Actions\Social\RecordSocialConversionAction;
use App\Events\SocialConversionAchieved;
use App\Models\Organization;
use App\Models\SocialConversion;
use App\Models\SocialLead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RecordSocialConversionActionTest extends TestCase
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
    public function test_records_conversion(): void
    {
        Event::fake([SocialConversionAchieved::class]);

        $result = app(RecordSocialConversionAction::class)->execute(
            organizationId: $this->organization->id,
            conversionType: 'demo_booked',
            actorId: $this->user->id,
            revenue: 299.00,
        );

        $this->assertInstanceOf(SocialConversion::class, $result);
        $this->assertEquals('demo_booked', $result->conversion_type);
        $this->assertEquals(299.00, $result->revenue);
        $this->assertDatabaseHas('social_conversions', ['conversion_type' => 'demo_booked']);
        Event::assertDispatched(SocialConversionAchieved::class);
    }

    #[Test]
    public function test_marks_lead_as_converted(): void
    {
        Event::fake();

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

        $this->assertEquals('converted', $lead->fresh()->status);
        $this->assertNotNull($lead->fresh()->converted_at);
    }
}
