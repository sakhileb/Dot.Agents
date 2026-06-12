<?php

namespace App\Policies;

use App\Models\AgentSkillRequirement;
use App\Models\User;

class AgentSkillRequirementPolicy
{
    public function viewAny(User $user): bool
    {
        return auth()->check();
    }

    public function view(User $user, AgentSkillRequirement $requirement): bool
    {
        return auth()->check(); // Platform-wide definitions
    }

    public function create(User $user): bool
    {
        return $user->hasRole('super-admin');
    }

    public function update(User $user, AgentSkillRequirement $requirement): bool
    {
        return $user->hasRole('super-admin');
    }

    public function delete(User $user, AgentSkillRequirement $requirement): bool
    {
        return $user->hasRole('super-admin');
    }
}
