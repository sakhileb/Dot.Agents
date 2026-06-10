<?php

namespace App\Actions\Organizations;

use App\Models\KnowledgeBase;
use App\Models\Organization;
use Illuminate\Support\Facades\Gate;

class SaveKnowledgeBaseAction
{
    public function execute(Organization $organization, array $data): KnowledgeBase
    {
        Gate::authorize('update', $organization);

        return KnowledgeBase::create([
            'organization_id' => $organization->id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'type' => 'general',
            'access_level' => $data['access_level'] ?? 'internal',
            'is_active' => true,
        ]);
    }
}
