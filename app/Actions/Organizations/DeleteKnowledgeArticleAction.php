<?php

namespace App\Actions\Organizations;

use App\Models\KnowledgeArticle;
use App\Models\Organization;
use Illuminate\Support\Facades\Gate;

class DeleteKnowledgeArticleAction
{
    public function execute(Organization $organization, int $articleId): void
    {
        Gate::authorize('update', $organization);

        $article = KnowledgeArticle::where('organization_id', $organization->id)
            ->findOrFail($articleId);

        $article->delete();
    }
}
