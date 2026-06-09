<?php

namespace App\Policies;

use App\Models\AgentScorecard;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AgentScorecardPolicy
{
    public function viewAny(User $user): bool
    {
        return auth()->check();
    }

    public function view(User $user, AgentScorecard $agentScorecard): bool
    {
        return $user->organizations()->where('organizations.id', $agentScorecard->organization_id)->exists();
    }

    /** Scorecards are system-generated; no manual creation allowed. */
    public function create(User $user): bool
    {
        return false;
    }

    /** Scorecards are immutable. */
    public function update(User $user, AgentScorecard $agentScorecard): bool
    {
        return false;
    }

    /** Only platform admins can purge scorecards. */
    public function delete(User $user, AgentScorecard $agentScorecard): bool
    {
        return $user->hasRole('platform_admin');
    }
}
