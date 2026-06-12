<?php

namespace App\Policies;

use App\Models\SocialMessage;
use App\Models\User;

class SocialMessagePolicy
{
    public function viewAny(User $user): bool
    {
        return auth()->check();
    }

    public function view(User $user, SocialMessage $message): bool
    {
        return $user->organizations()->where('organizations.id', $message->organization_id)->exists();
    }

    public function create(User $user): bool
    {
        return false; // Created only by Social Commerce ingestion
    }

    public function update(User $user, SocialMessage $message): bool
    {
        return $user->organizations()
            ->where('organizations.id', $message->organization_id)
            ->wherePivotIn('role', ['owner', 'admin'])
            ->exists();
    }

    public function delete(User $user, SocialMessage $message): bool
    {
        return $user->hasRole('super-admin');
    }
}
