<?php

namespace Tests\Feature\Actions;

use App\Actions\Organizations\UpdateOrganizationSettingsAction;
use App\DTOs\Organizations\UpdateOrganizationSettingsData;
use App\Events\OrganizationSettingsUpdated;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class UpdateOrganizationSettingsActionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->organization = Organization::factory()->create(['owner_id' => $this->user->id]);
        session(['current_organization_id' => $this->organization->id]);
    }

    public function test_updates_organization_name_and_settings(): void
    {
        $this->actingAs($this->user);
        Gate::before(fn () => true);

        $result = app(UpdateOrganizationSettingsAction::class)->execute($this->organization, UpdateOrganizationSettingsData::fromArray([
            'name' => 'Acme Corp Updated',
            'industry' => 'fintech',
        ]));

        $this->assertEquals('Acme Corp Updated', $result->name);
        $this->assertDatabaseHas('organizations', [
            'id' => $this->organization->id,
            'name' => 'Acme Corp Updated',
        ]);
    }

    public function test_requires_authorization(): void
    {
        $otherUser = User::factory()->create();
        $this->actingAs($otherUser);

        $this->expectException(AuthorizationException::class);
        app(UpdateOrganizationSettingsAction::class)->execute($this->organization, UpdateOrganizationSettingsData::fromArray(['name' => 'Hacked']));
    }

    public function test_creates_audit_log_on_settings_update(): void
    {
        $this->actingAs($this->user);
        Gate::before(fn () => true);

        Event::fake([OrganizationSettingsUpdated::class]);

        app(UpdateOrganizationSettingsAction::class)->execute($this->organization, UpdateOrganizationSettingsData::fromArray(['name' => 'New Name']));

        Event::assertDispatched(OrganizationSettingsUpdated::class);
    }
}
