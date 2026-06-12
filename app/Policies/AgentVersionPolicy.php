<?php

namespace App\Policies;

use App\Models\AgentVersion;
use App\Models\User;

class AgentVersionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, AgentVersion $version): bool
    {
        return $user->hasRole('platform_admin')
            || $user->organizations()->whereHas('agents', fn ($q) => $q->where('agents.id', $version->agent_id))->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasRole('platform_admin');
    }

    public function update(User $user, AgentVersion $version): bool
    {
        return $user->hasRole('platform_admin');
    }

    public function delete(User $user, AgentVersion $version): bool
    {
        return $user->hasRole('platform_admin') && ! $version->is_current;
    }

    public function restore(User $user, AgentVersion $version): bool
    {
        return $user->hasRole('platform_admin');
    }

    public function forceDelete(User $user, AgentVersion $version): bool
    {
        return $user->hasRole('platform_admin');
    }

    public function rollback(User $user, AgentVersion $version): bool
    {
        return $user->hasRole('platform_admin');
    }
}


    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, AgentVersion $agentVersion): bool
    {
        return false;
    }
}
