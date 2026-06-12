<?php

namespace App\Policies;

use App\Models\AgentToolPermission;
use App\Models\User;

class AgentToolPermissionPolicy
{
    public function viewAny(User $user): bool
    {
        return auth()->check();
    }

    public function view(User $user, AgentToolPermission $permission): bool
    {
        return auth()->check(); // Platform-wide definitions
    }

    public function create(User $user): bool
    {
        return $user->hasRole('super-admin');
    }

    public function update(User $user, AgentToolPermission $permission): bool
    {
        return $user->hasRole('super-admin');
    }

    public function delete(User $user, AgentToolPermission $permission): bool
    {
        return $user->hasRole('super-admin');
    }
}
