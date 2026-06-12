<?php

namespace App\Policies;

use App\Models\AgentSkillPermission;
use App\Models\User;

class AgentSkillPermissionPolicy
{
    public function viewAny(User $user): bool
    {
        return auth()->check();
    }

    public function view(User $user, AgentSkillPermission $permission): bool
    {
        return auth()->check(); // Platform-wide definitions
    }

    public function create(User $user): bool
    {
        return $user->hasRole('super-admin');
    }

    public function update(User $user, AgentSkillPermission $permission): bool
    {
        return $user->hasRole('super-admin');
    }

    public function delete(User $user, AgentSkillPermission $permission): bool
    {
        return $user->hasRole('super-admin');
    }
}
