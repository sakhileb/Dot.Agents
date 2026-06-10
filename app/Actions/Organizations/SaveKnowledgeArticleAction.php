<?php

namespace App\Actions\Organizations;

use App\Models\KnowledgeArticle;
use App\Models\Organization;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class SaveKnowledgeArticleAction
{
    public function execute(Organization $organization, array $data, ?int $existingId = null): KnowledgeArticle
    {
        Gate::authorize('update', $organization);

        $payload = [
            'knowledge_base_id' => $data['knowledge_base_id'],
            'organization_id' => $organization->id,
            'author_id' => auth()->id(),
            'title' => $data['title'],
            'slug' => Str::slug($data['title']).'-'.time(),
            'content' => $data['content'],
            'summary' => $data['summary'] ?? null,
            'category' => $data['category'] ?? null,
            'status' => 'published',
            'published_at' => now(),
        ];

        if ($existingId) {
            $article = KnowledgeArticle::findOrFail($existingId);

            // Verify the article belongs to this organization
            abort_if($article->organization_id !== $organization->id, 403);

            $article->update($payload);

            return $article->fresh();
        }

        return KnowledgeArticle::create($payload);
    }
}
