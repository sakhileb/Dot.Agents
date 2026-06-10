<?php

namespace App\Policies;

use App\Models\AgentDeployment;
use App\Models\AgentSkill;
use App\Models\User;

class AgentSkillPolicy
{
    public function viewAny(User $user): bool
    {
        return auth()->check();
    }

    /** Skills are platform-wide catalog — any authenticated user can view. */
    public function view(User $user, AgentSkill $skill): bool
    {
        return auth()->check();
    }

    /** Only platform admins (role = 'admin' in any org) can create skills. */
    public function create(User $user): bool
    {
        return $user->hasRole('admin') || $user->organizations()
            ->wherePivotIn('role', ['owner', 'admin'])
            ->exists();
    }

    public function update(User $user, AgentSkill $skill): bool
    {
        return $user->hasRole('admin') || $user->organizations()
            ->wherePivotIn('role', ['owner', 'admin'])
            ->exists();
    }

    public function delete(User $user, AgentSkill $skill): bool
    {
        return $user->hasRole('admin');
    }

    public function forceDelete(User $user, AgentSkill $skill): bool
    {
        return false;
    }

    /**
     * Authorize skill execution: user must belong to the deployment's org
     * and the deployment must be in an active state.
     */
    public function execute(User $user, AgentSkill $skill, AgentDeployment $deployment): bool
    {
        if ($deployment->status !== 'active') {
            return false;
        }

        return $user->organizations()
            ->where('organizations.id', $deployment->organization_id)
            ->exists();
    }
}
