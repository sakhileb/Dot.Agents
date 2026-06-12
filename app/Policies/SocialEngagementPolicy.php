<?php

namespace App\Policies;

use App\Models\SocialEngagement;
use App\Models\User;

class SocialEngagementPolicy
{
    public function viewAny(User $user): bool
    {
        return auth()->check();
    }

    public function view(User $user, SocialEngagement $engagement): bool
    {
        return $user->organizations()->where('organizations.id', $engagement->organization_id)->exists();
    }

    public function create(User $user): bool
    {
        return false; // Created only by Social Commerce agents
    }

    public function update(User $user, SocialEngagement $engagement): bool
    {
        return false; // Engagement records are immutable
    }

    public function delete(User $user, SocialEngagement $engagement): bool
    {
        return $user->hasRole('super-admin');
    }
}
