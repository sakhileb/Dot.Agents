<?php

namespace Tests\Feature\Actions\Organizations;

use App\Actions\Organizations\RevokeApiTokenAction;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RevokeApiTokenActionTest extends TestCase
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
    public function test_revokes_token(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $tokenResult = $user->createToken('my-token');
        $token = $tokenResult->accessToken;

        $this->assertDatabaseHas('personal_access_tokens', ['id' => $token->id]);

        app(RevokeApiTokenAction::class)->execute($token);

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token->id]);
    }
}
