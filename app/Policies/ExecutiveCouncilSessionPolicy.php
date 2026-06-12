<?php

namespace App\Policies;

use App\Models\ExecutiveCouncilSession;
use App\Models\User;

class ExecutiveCouncilSessionPolicy
{
    public function viewAny(User $user): bool
    {
        return auth()->check();
    }

    public function view(User $user, ExecutiveCouncilSession $session): bool
    {
        return $user->organizations()->where('organizations.id', $session->organization_id)->exists();
    }

    public function create(User $user): bool
    {
        // Only org owners/admins can initiate Executive Council sessions
        return auth()->check();
    }

    public function update(User $user, ExecutiveCouncilSession $session): bool
    {
        return $user->organizations()
            ->where('organizations.id', $session->organization_id)
            ->wherePivotIn('role', ['owner', 'admin'])
            ->exists();
    }

    public function delete(User $user, ExecutiveCouncilSession $session): bool
    {
        return $user->organizations()
            ->where('organizations.id', $session->organization_id)
            ->wherePivot('role', 'owner')
            ->exists();
    }
}
