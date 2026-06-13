<?php

namespace Tests\Feature\Actions\Organizations;

use App\Actions\Organizations\DeleteKnowledgeArticleAction;
use App\DTOs\Organizations\DeleteKnowledgeArticleData;
use App\Models\KnowledgeArticle;
use App\Models\KnowledgeBase;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeleteKnowledgeArticleActionTest extends TestCase
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
    public function test_deletes_knowledge_article(): void
    {
        $kb = KnowledgeBase::create([
            'organization_id' => $this->organization->id,
            'name' => 'Test KB',
            'slug' => 'test-kb-'.uniqid(),
            'is_active' => true,
        ]);

        $article = KnowledgeArticle::create([
            'knowledge_base_id' => $kb->id,
            'organization_id' => $this->organization->id,
            'created_by' => $this->user->id,
            'title' => 'To Delete',
            'slug' => 'to-delete-'.time(),
            'content' => 'Content.',
            'is_published' => true,
        ]);

        $data = DeleteKnowledgeArticleData::fromId($article->id);
        app(DeleteKnowledgeArticleAction::class)->execute($this->organization, $data);

        $this->assertSoftDeleted('knowledge_articles', ['id' => $article->id]);
    }
}
