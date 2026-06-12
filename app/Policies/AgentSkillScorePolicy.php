<?php

namespace App\Policies;

use App\Models\AgentSkillScore;
use App\Models\User;

class AgentSkillScorePolicy
{
    public function viewAny(User $user): bool
    {
        return auth()->check();
    }

    public function view(User $user, AgentSkillScore $score): bool
    {
        return $user->organizations()->where('organizations.id', $score->organization_id)->exists();
    }

    public function create(User $user): bool
    {
        return false; // Created only by RecordSkillScoreAction (system job)
    }

    public function update(User $user, AgentSkillScore $score): bool
    {
        return false; // Scores are system-managed
    }

    public function delete(User $user, AgentSkillScore $score): bool
    {
        return $user->hasRole('super-admin');
    }
}
