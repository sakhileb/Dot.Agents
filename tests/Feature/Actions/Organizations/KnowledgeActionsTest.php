<?php

namespace Tests\Feature\Actions\Organizations;

use App\Actions\Organizations\DeleteKnowledgeArticleAction;
use App\Actions\Organizations\RevokeApiTokenAction;
use App\Actions\Organizations\SaveConnectionSettingsAction;
use App\Actions\Organizations\SaveKnowledgeArticleAction;
use App\Actions\Organizations\SaveKnowledgeBaseAction;
use App\DTOs\Organizations\DeleteKnowledgeArticleData;
use App\DTOs\Organizations\SaveConnectionSettingsData;
use App\Models\KnowledgeArticle;
use App\Models\KnowledgeBase;
use App\Models\Organization;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class KnowledgeActionsTest extends TestCase
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

    // ─── SaveKnowledgeBaseAction ──────────────────────────────────────────────

    public function test_save_knowledge_base_creates_record(): void
    {
        $data = ['name' => 'Product FAQ', 'description' => 'Frequently asked questions', 'type' => 'support', 'access_level' => 'internal'];

        $kb = app(SaveKnowledgeBaseAction::class)->execute($this->organization, $data);

        $this->assertDatabaseHas('knowledge_bases', [
            'id' => $kb->id,
            'organization_id' => $this->organization->id,
            'name' => 'Product FAQ',
            'type' => 'support',
        ]);
    }

    // ─── SaveKnowledgeArticleAction ───────────────────────────────────────────

    public function test_save_knowledge_article_creates_new_record(): void
    {
        $kb = KnowledgeBase::create([
            'organization_id' => $this->organization->id,
            'name' => 'Test KB',
            'slug' => 'test-kb-abc123',
            'type' => 'general',
            'access_level' => 'internal',
            'created_by' => $this->user->id,
        ]);

        $data = [
            'knowledge_base_id' => $kb->id,
            'title' => 'How to onboard',
            'content' => 'Step 1: complete profile. Step 2: invite team.',
        ];

        $article = app(SaveKnowledgeArticleAction::class)->execute($this->organization, $data);

        $this->assertDatabaseHas('knowledge_articles', [
            'id' => $article->id,
            'organization_id' => $this->organization->id,
            'knowledge_base_id' => $kb->id,
            'title' => 'How to onboard',
        ]);
    }

    public function test_save_knowledge_article_updates_existing_record(): void
    {
        $kb = KnowledgeBase::create([
            'organization_id' => $this->organization->id,
            'name' => 'Test KB',
            'slug' => 'test-kb-def456',
            'type' => 'general',
            'access_level' => 'internal',
            'created_by' => $this->user->id,
        ]);

        $article = KnowledgeArticle::create([
            'knowledge_base_id' => $kb->id,
            'organization_id' => $this->organization->id,
            'created_by' => $this->user->id,
            'title' => 'Old Title',
            'slug' => 'old-title-'.time(),
            'content' => 'Old content.',
            'is_published' => true,
        ]);

        $data = [
            'knowledge_base_id' => $kb->id,
            'title' => 'Updated Title',
            'content' => 'Updated content.',
        ];

        app(SaveKnowledgeArticleAction::class)->execute($this->organization, $data, $article->id);

        $this->assertDatabaseHas('knowledge_articles', [
            'id' => $article->id,
            'title' => 'Updated Title',
            'content' => 'Updated content.',
        ]);
    }

    // ─── SaveConnectionSettingsAction ─────────────────────────────────────────

    public function test_save_connection_settings_creates_record(): void
    {
        $account = SocialAccount::factory()->create([
            'organization_id' => $this->organization->id,
            'platform' => 'instagram',
        ]);

        $data = new SaveConnectionSettingsData(
            goals: ['brand_awareness', 'lead_generation'],
            aiFeatures: ['auto_reply', 'sentiment_analysis'],
            permissions: ['post', 'read_messages'],
            autonomyLevel: 2
        );

        $settings = app(SaveConnectionSettingsAction::class)->execute($account, $data);

        $this->assertDatabaseHas('social_connection_settings', [
            'id' => $settings->id,
            'social_account_id' => $account->id,
            'platform' => 'instagram',
            'autonomy_level' => 2,
        ]);
    }

    // ─── DeleteKnowledgeArticleAction ─────────────────────────────────────────

    public function test_delete_knowledge_article_soft_deletes_record(): void
    {
        $kb = KnowledgeBase::create([
            'organization_id' => $this->organization->id,
            'name' => 'Test KB',
            'slug' => 'test-kb-ghi789',
            'type' => 'general',
            'access_level' => 'internal',
            'created_by' => $this->user->id,
        ]);

        $article = KnowledgeArticle::create([
            'knowledge_base_id' => $kb->id,
            'organization_id' => $this->organization->id,
            'created_by' => $this->user->id,
            'title' => 'To Be Deleted',
            'slug' => 'to-be-deleted-'.time(),
            'content' => 'Some content.',
            'is_published' => true,
        ]);

        $data = DeleteKnowledgeArticleData::fromId($article->id);

        app(DeleteKnowledgeArticleAction::class)->execute($this->organization, $data);

        $this->assertSoftDeleted('knowledge_articles', ['id' => $article->id]);
    }

    // ─── RevokeApiTokenAction ─────────────────────────────────────────────────

    public function test_revoke_api_token_deletes_the_token(): void
    {
        $newUser = User::factory()->create();
        $token = $newUser->createToken('CI Test Token');
        $accessToken = $token->accessToken;

        $this->actingAs($newUser);

        app(RevokeApiTokenAction::class)->execute($accessToken);

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $accessToken->id]);
    }
}
