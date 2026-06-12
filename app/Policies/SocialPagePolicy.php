<?php

namespace App\Policies;

use App\Models\SocialPage;
use App\Models\User;

class SocialPagePolicy
{
    public function viewAny(User $user): bool
    {
        return auth()->check();
    }

    public function view(User $user, SocialPage $page): bool
    {
        return $user->organizations()->where('organizations.id', $page->organization_id)->exists();
    }

    public function create(User $user): bool
    {
        return auth()->check();
    }

    public function update(User $user, SocialPage $page): bool
    {
        return $user->organizations()
            ->where('organizations.id', $page->organization_id)
            ->wherePivotIn('role', ['owner', 'admin'])
            ->exists();
    }

    public function delete(User $user, SocialPage $page): bool
    {
        return $user->organizations()
            ->where('organizations.id', $page->organization_id)
            ->wherePivotIn('role', ['owner', 'admin'])
            ->exists();
    }
}
