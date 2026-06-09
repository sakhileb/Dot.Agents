<?php

namespace Tests\Feature\Actions\Organizations;

use App\Actions\Organizations\CreateOrganizationAction;
use App\DTOs\Organizations\CreateOrganizationData;
use App\Events\OrganizationCreated;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class CreateOrganizationActionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Gate::before(fn () => true);
    }

    public function test_creates_organization_with_correct_fields(): void
    {
        $owner = User::factory()->create();
        $this->actingAs($owner);

        $data = new CreateOrganizationData(
            name: 'Acme Corp',
            industry: 'technology',
            size: 'medium',
        );

        $organization = app(CreateOrganizationAction::class)->execute($data, $owner);

        $this->assertInstanceOf(Organization::class, $organization);
        $this->assertSame('Acme Corp', $organization->name);
        $this->assertSame('technology', $organization->industry);
        $this->assertSame('starter', $organization->plan);
        $this->assertSame('active', $organization->status);
        $this->assertSame($owner->id, $organization->owner_id);
    }

    public function test_owner_is_attached_as_organization_member(): void
    {
        $owner = User::factory()->create();
        $this->actingAs($owner);

        $data = new CreateOrganizationData(name: 'Test Org', industry: 'finance', size: 'small');
        $organization = app(CreateOrganizationAction::class)->execute($data, $owner);

        $this->assertDatabaseHas('organization_user', [
            'organization_id' => $organization->id,
            'user_id' => $owner->id,
            'role' => 'owner',
        ]);
    }

    public function test_fires_organization_created_event(): void
    {
        Event::fake([OrganizationCreated::class]);
        $owner = User::factory()->create();
        $this->actingAs($owner);

        $data = new CreateOrganizationData(name: 'Event Org', industry: 'healthcare', size: 'large');
        app(CreateOrganizationAction::class)->execute($data, $owner);

        Event::assertDispatched(OrganizationCreated::class);
    }

    public function test_organization_gets_14_day_trial(): void
    {
        $owner = User::factory()->create();
        $this->actingAs($owner);

        $data = new CreateOrganizationData(name: 'Trial Org', industry: 'retail', size: 'small');
        $organization = app(CreateOrganizationAction::class)->execute($data, $owner);

        $this->assertNotNull($organization->trial_ends_at);
        $this->assertTrue($organization->trial_ends_at->isFuture());
    }

    public function test_slug_is_generated_from_name(): void
    {
        $owner = User::factory()->create();
        $this->actingAs($owner);

        $data = new CreateOrganizationData(name: 'My Company', industry: 'logistics', size: 'medium');
        $organization = app(CreateOrganizationAction::class)->execute($data, $owner);

        $this->assertStringStartsWith('my-company', $organization->slug);
    }
}
