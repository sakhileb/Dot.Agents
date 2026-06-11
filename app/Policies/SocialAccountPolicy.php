<?php

namespace App\Policies;

use App\Models\SocialAccount;
use App\Models\User;

class SocialAccountPolicy
{
    public function viewAny(User $user): bool
    {
        return auth()->check();
    }

    public function view(User $user, SocialAccount $account): bool
    {
        return $user->organizations()->where('organizations.id', $account->organization_id)->exists();
    }

    public function create(User $user, int $organizationId): bool
    {
        return $user->organizations()
            ->where('organizations.id', $organizationId)
            ->wherePivotIn('role', ['owner', 'admin'])
            ->exists();
    }

    public function update(User $user, SocialAccount $account): bool
    {
        return $user->organizations()
            ->where('organizations.id', $account->organization_id)
            ->wherePivotIn('role', ['owner', 'admin'])
            ->exists();
    }

    public function delete(User $user, SocialAccount $account): bool
    {
        return $user->organizations()
            ->where('organizations.id', $account->organization_id)
            ->wherePivotIn('role', ['owner', 'admin'])
            ->exists();
    }
}
