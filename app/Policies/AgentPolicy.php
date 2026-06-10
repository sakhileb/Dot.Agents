<?php

namespace App\Policies;

use App\Models\Agent;
use App\Models\User;

class AgentPolicy
{
    /**
     * All authenticated users can browse the marketplace.
     */
    public function viewAny(User $user): bool
    {
        return auth()->check();
    }

    /**
     * Any authenticated user can view an active, non-enterprise-only agent.
     * Enterprise-only agents require the org to be on an enterprise plan.
     */
    public function view(User $user, Agent $agent): bool
    {
        if (! $agent->is_enterprise_only) {
            return true;
        }

        $orgId = session('current_organization_id');
        if (! $orgId) {
            return false;
        }

        return $user->organizations()
            ->where('organizations.id', (int) $orgId)
            ->whereIn('organizations.plan', ['enterprise', 'enterprise_plus'])
            ->exists();
    }

    /**
     * Only platform administrators can create new agents (i.e., add to marketplace).
     * Regular org users cannot create platform-level agents.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('platform_admin');
    }

    /**
     * Only platform administrators can update agent definitions.
     */
    public function update(User $user, Agent $agent): bool
    {
        return $user->hasRole('platform_admin');
    }

    /**
     * Only platform administrators can delete agents from the marketplace.
     */
    public function delete(User $user, Agent $agent): bool
    {
        return $user->hasRole('platform_admin') && $agent->total_deployments === 0;
    }

    /**
     * Any org member can deploy an agent (subject to plan restrictions).
     */
    public function deploy(User $user, Agent $agent): bool
    {
        $orgId = session('current_organization_id');

        if (! $orgId) {
            return false;
        }

        $isMember = $user->organizations()
            ->where('organizations.id', $orgId)
            ->exists();

        if (! $isMember) {
            return false;
        }

        // Enterprise-only agents require enterprise plan
        if ($agent->is_enterprise_only) {
            return $user->organizations()
                ->where('organizations.id', $orgId)
                ->whereIn('organizations.plan', ['enterprise', 'enterprise_plus'])
                ->exists();
        }

        return true;
    }

    /**
     * Only platform admins can verify/certify agents.
     */
    public function certify(User $user, Agent $agent): bool
    {
        return $user->hasRole('platform_admin');
    }
}
