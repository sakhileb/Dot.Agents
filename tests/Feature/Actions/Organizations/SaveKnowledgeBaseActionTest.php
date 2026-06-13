<?php

namespace Tests\Feature\Actions\Organizations;

use App\Actions\Organizations\SaveKnowledgeBaseAction;
use App\Models\KnowledgeBase;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SaveKnowledgeBaseActionTest extends TestCase
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
    public function test_creates_knowledge_base(): void
    {
        $result = app(SaveKnowledgeBaseAction::class)->execute($this->organization, [
            'name' => 'HR Policies',
            'description' => 'HR policy documents',
            'type' => 'policy',
            'access_level' => 'internal',
        ]);

        $this->assertInstanceOf(KnowledgeBase::class, $result);
        $this->assertEquals('HR Policies', $result->name);
        $this->assertEquals($this->organization->id, $result->organization_id);
        $this->assertTrue($result->is_active);
    }

    #[Test]
    public function test_generates_slug(): void
    {
        $result = app(SaveKnowledgeBaseAction::class)->execute($this->organization, [
            'name' => 'Tech Documentation',
        ]);

        $this->assertNotNull($result->name);
    }
}
