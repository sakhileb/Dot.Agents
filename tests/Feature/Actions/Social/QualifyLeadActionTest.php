<?php

namespace Tests\Feature\Actions\Social;

use App\Actions\Social\QualifyLeadAction;
use App\Models\Organization;
use App\Models\SocialLead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class QualifyLeadActionTest extends TestCase
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
    public function test_qualifies_lead_with_high_intent(): void
    {
        $lead = SocialLead::factory()->create([
            'organization_id' => $this->organization->id,
            'intent_score' => 90,
            'lead_score' => 88,
            'status' => 'new',
        ]);

        $result = app(QualifyLeadAction::class)->execute($lead, $this->user->id);

        $this->assertEquals('qualified', $result->status);
        $this->assertEquals('high_intent', $result->intent_level);
        $this->assertEquals('hot', $result->priority);
        $this->assertNotNull($result->qualified_at);
        $this->assertContains('transfer_to_sales', $result->recommended_actions);
    }

    #[Test]
    public function test_qualifies_lead_with_browsing_intent(): void
    {
        $lead = SocialLead::factory()->create([
            'organization_id' => $this->organization->id,
            'intent_score' => 10,
            'lead_score' => 20,
            'status' => 'new',
        ]);

        $result = app(QualifyLeadAction::class)->execute($lead, $this->user->id);

        $this->assertEquals('browsing', $result->intent_level);
        $this->assertEquals('low', $result->priority);
    }
}
