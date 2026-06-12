<?php

namespace App\Policies;

use App\Models\SocialConnectionSettings;
use App\Models\User;

class SocialConnectionSettingsPolicy
{
    public function viewAny(User $user): bool
    {
        return auth()->check();
    }

    public function view(User $user, SocialConnectionSettings $settings): bool
    {
        return $user->organizations()->where('organizations.id', $settings->organization_id)->exists();
    }

    public function create(User $user): bool
    {
        return auth()->check();
    }

    public function update(User $user, SocialConnectionSettings $settings): bool
    {
        return $user->organizations()
            ->where('organizations.id', $settings->organization_id)
            ->wherePivotIn('role', ['owner', 'admin'])
            ->exists();
    }

    public function delete(User $user, SocialConnectionSettings $settings): bool
    {
        return $user->organizations()
            ->where('organizations.id', $settings->organization_id)
            ->wherePivotIn('role', ['owner', 'admin'])
            ->exists();
    }
}
