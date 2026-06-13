<?php

namespace Tests\Feature\Actions\Organizations;

use App\Actions\Organizations\SaveKnowledgeArticleAction;
use App\Models\KnowledgeArticle;
use App\Models\KnowledgeBase;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SaveKnowledgeArticleActionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Organization $organization;

    private KnowledgeBase $kb;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->organization = Organization::factory()->create(['owner_id' => $this->user->id]);
        $this->kb = KnowledgeBase::create([
            'organization_id' => $this->organization->id,
            'name' => 'Test KB',
            'slug' => 'test-kb-'.uniqid(),
            'is_active' => true,
        ]);
        $this->actingAs($this->user);
        Gate::before(fn () => true);
        session(['current_organization_id' => $this->organization->id]);
    }

    #[Test]
    public function test_creates_knowledge_article(): void
    {
        $result = app(SaveKnowledgeArticleAction::class)->execute($this->organization, [
            'knowledge_base_id' => $this->kb->id,
            'title' => 'Getting Started',
            'content' => 'Welcome to the platform.',
        ]);

        $this->assertInstanceOf(KnowledgeArticle::class, $result);
        $this->assertEquals('Getting Started', $result->title);
        $this->assertEquals($this->organization->id, $result->organization_id);
        $this->assertTrue($result->is_published);
    }

    #[Test]
    public function test_updates_existing_article(): void
    {
        $article = KnowledgeArticle::create([
            'knowledge_base_id' => $this->kb->id,
            'organization_id' => $this->organization->id,
            'created_by' => $this->user->id,
            'title' => 'Old Title',
            'slug' => 'old-title-'.time(),
            'content' => 'Old content.',
            'is_published' => true,
        ]);

        $result = app(SaveKnowledgeArticleAction::class)->execute($this->organization, [
            'knowledge_base_id' => $this->kb->id,
            'title' => 'Updated Title',
            'content' => 'Updated content.',
        ], $article->id);

        $this->assertEquals('Updated Title', $result->title);
        $this->assertDatabaseHas('knowledge_articles', ['id' => $article->id, 'title' => 'Updated Title']);
    }
}
