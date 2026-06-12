<?php

namespace App\Policies;

use App\Models\AgentSkillExecution;
use App\Models\User;

class AgentSkillExecutionPolicy
{
    public function viewAny(User $user): bool
    {
        return auth()->check();
    }

    public function view(User $user, AgentSkillExecution $execution): bool
    {
        return $user->organizations()->where('organizations.id', $execution->organization_id)->exists();
    }

    public function create(User $user): bool
    {
        return false; // Created only by system (Job runner)
    }

    public function update(User $user, AgentSkillExecution $execution): bool
    {
        return false; // Execution records are immutable
    }

    public function delete(User $user, AgentSkillExecution $execution): bool
    {
        return $user->hasRole('super-admin');
    }
}
