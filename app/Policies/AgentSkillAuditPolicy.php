<?php

namespace App\Policies;

use App\Models\AgentSkillAudit;
use App\Models\User;

class AgentSkillAuditPolicy
{
    public function viewAny(User $user): bool
    {
        return auth()->check();
    }

    public function view(User $user, AgentSkillAudit $audit): bool
    {
        return $user->organizations()->where('organizations.id', $audit->organization_id)->exists();
    }

    public function create(User $user): bool
    {
        return false; // Created only by system (DWCA audit runner)
    }

    public function update(User $user, AgentSkillAudit $audit): bool
    {
        return false; // Audit records are immutable
    }

    public function delete(User $user, AgentSkillAudit $audit): bool
    {
        return $user->hasRole('super-admin');
    }
}
