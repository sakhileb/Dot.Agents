<?php

namespace App\Policies;

use App\Models\ExecutiveRecommendation;
use App\Models\User;

class ExecutiveRecommendationPolicy
{
    public function viewAny(User $user): bool
    {
        return auth()->check();
    }

    public function view(User $user, ExecutiveRecommendation $recommendation): bool
    {
        return $user->organizations()->where('organizations.id', $recommendation->organization_id)->exists();
    }

    public function create(User $user): bool
    {
        return false; // Created only by Executive Council AI agents
    }

    public function update(User $user, ExecutiveRecommendation $recommendation): bool
    {
        // Only org owners/admins can act on recommendations
        return $user->organizations()
            ->where('organizations.id', $recommendation->organization_id)
            ->wherePivotIn('role', ['owner', 'admin'])
            ->exists();
    }

    public function delete(User $user, ExecutiveRecommendation $recommendation): bool
    {
        return $user->hasRole('super-admin');
    }
}
