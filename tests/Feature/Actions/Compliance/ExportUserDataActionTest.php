<?php

namespace Tests\Feature\Actions\Compliance;

use App\Actions\Compliance\ExportUserDataAction;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ExportUserDataActionTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();
        Gate::before(fn () => true);
        $this->organization = Organization::factory()->create();
        session(['current_organization_id' => $this->organization->id]);
    }

    #[Test]
    public function test_returns_structured_export_payload(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $result = app(ExportUserDataAction::class)->execute($user, $user);

        $this->assertArrayHasKey('exported_at', $result);
        $this->assertArrayHasKey('subject', $result);
        $this->assertArrayHasKey('profile', $result);
        $this->assertArrayHasKey('organizations', $result);
        $this->assertArrayHasKey('audit_activity', $result);
        $this->assertArrayHasKey('notifications', $result);
    }

    #[Test]
    public function test_subject_contains_correct_user_data(): void
    {
        $user = User::factory()->create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
        $this->actingAs($user);

        $result = app(ExportUserDataAction::class)->execute($user, $user);

        $this->assertEquals($user->id, $result['subject']['id']);
        $this->assertEquals('Jane Doe', $result['subject']['name']);
        $this->assertEquals('jane@example.com', $result['subject']['email']);
    }

    #[Test]
    public function test_includes_organization_memberships(): void
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create(['owner_id' => $user->id]);
        $user->organizations()->attach($org->id, ['role' => 'admin']);
        $this->actingAs($user);

        $result = app(ExportUserDataAction::class)->execute($user, $user);

        $orgIds = array_column($result['organizations'], 'id');
        $this->assertContains($org->id, $orgIds);
    }
}
