<?php

namespace App\Policies;

use App\Models\SocialCampaign;
use App\Models\User;

class SocialCampaignPolicy
{
    public function viewAny(User $user): bool
    {
        return auth()->check();
    }

    public function view(User $user, SocialCampaign $campaign): bool
    {
        return $user->organizations()->where('organizations.id', $campaign->organization_id)->exists();
    }

    public function create(User $user): bool
    {
        return auth()->check();
    }

    public function update(User $user, SocialCampaign $campaign): bool
    {
        return $user->organizations()
            ->where('organizations.id', $campaign->organization_id)
            ->wherePivotIn('role', ['owner', 'admin'])
            ->exists();
    }

    public function delete(User $user, SocialCampaign $campaign): bool
    {
        return $user->organizations()
            ->where('organizations.id', $campaign->organization_id)
            ->wherePivotIn('role', ['owner', 'admin'])
            ->exists();
    }
}
