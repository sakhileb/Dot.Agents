<?php

namespace App\Policies;

use App\Models\EnterpriseDecision;
use App\Models\User;

class EnterpriseDecisionPolicy
{
    public function viewAny(User $user): bool
    {
        return auth()->check();
    }

    public function view(User $user, EnterpriseDecision $decision): bool
    {
        return $user->organizations()->where('organizations.id', $decision->organization_id)->exists();
    }

    public function create(User $user): bool
    {
        return false; // Created only by Executive Council AI agents
    }

    public function update(User $user, EnterpriseDecision $decision): bool
    {
        // Only org owners/admins can approve/reject decisions
        return $user->organizations()
            ->where('organizations.id', $decision->organization_id)
            ->wherePivotIn('role', ['owner', 'admin'])
            ->exists();
    }

    public function delete(User $user, EnterpriseDecision $decision): bool
    {
        return $user->hasRole('super-admin');
    }
}
