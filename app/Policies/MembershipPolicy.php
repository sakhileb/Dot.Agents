<?php

namespace App\Policies;

use App\Models\Membership;
use App\Models\User;

class MembershipPolicy
{
    public function viewAny(User $user): bool
    {
        return auth()->check();
    }

    public function view(User $user, Membership $membership): bool
    {
        return $user->organizations()->where('organizations.id', $membership->organization_id)->exists();
    }

    public function create(User $user): bool
    {
        return auth()->check();
    }

    public function update(User $user, Membership $membership): bool
    {
        return $user->organizations()
            ->where('organizations.id', $membership->organization_id)
            ->wherePivotIn('role', ['owner', 'admin'])
            ->exists();
    }

    public function delete(User $user, Membership $membership): bool
    {
        return $user->organizations()
            ->where('organizations.id', $membership->organization_id)
            ->wherePivot('role', 'owner')
            ->exists();
    }
}
