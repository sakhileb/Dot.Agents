<?php

namespace App\Policies;

use App\Models\AgentDeployment;
use App\Models\User;

class AgentDeploymentPolicy
{
    /** Only org members can view deployments. */
    public function view(User $user, AgentDeployment $deployment): bool
    {
        return $user->organizations()->where('organizations.id', $deployment->organization_id)->exists();
    }

    /** Any authenticated org member can view the list. */
    public function viewAny(User $user): bool
    {
        return auth()->check();
    }

    /** Org members can create deployments in their org. */
    public function create(User $user, int $organizationId): bool
    {
        return $user->organizations()->where('organizations.id', $organizationId)->exists();
    }

    /** Only org owners/admins can update deployments. */
    public function update(User $user, AgentDeployment $deployment): bool
    {
        return $user->organizations()
            ->where('organizations.id', $deployment->organization_id)
            ->wherePivotIn('role', ['owner', 'admin'])
            ->exists();
    }

    /** Only org owners can delete (decommission) deployments. */
    public function delete(User $user, AgentDeployment $deployment): bool
    {
        return $user->organizations()
            ->where('organizations.id', $deployment->organization_id)
            ->wherePivot('role', 'owner')
            ->exists();
    }

    /** Check the deployment belongs to the current org session. */
    public function chat(User $user, AgentDeployment $deployment): bool
    {
        return $this->view($user, $deployment) && $deployment->status === 'active';
    }
}
