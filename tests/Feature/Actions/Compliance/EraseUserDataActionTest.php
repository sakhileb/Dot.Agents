<?php

namespace Tests\Feature\Actions\Compliance;

use App\Actions\Compliance\EraseUserDataAction;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EraseUserDataActionTest extends TestCase
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
    public function test_pseudonymises_user_personal_data(): void
    {
        $user = User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $this->actingAs($user);

        app(EraseUserDataAction::class)->execute($user, $user);

        $user->refresh();
        $this->assertStringContainsString('erased-', $user->email);
        $this->assertStringContainsString('Erased User', $user->name);
        $this->assertNotNull($user->erased_at);
    }

    #[Test]
    public function test_revokes_api_tokens(): void
    {
        $user = User::factory()->create();
        $user->createToken('test-token');
        $this->actingAs($user);

        $this->assertCount(1, $user->tokens);

        app(EraseUserDataAction::class)->execute($user, $user);

        $this->assertCount(0, $user->fresh()->tokens);
    }

    #[Test]
    public function test_invalidates_password(): void
    {
        $originalHash = User::factory()->create()->password;
        $user = User::factory()->create();
        $this->actingAs($user);

        app(EraseUserDataAction::class)->execute($user, $user);

        $this->assertNotEquals($originalHash, $user->fresh()->password);
    }
}
