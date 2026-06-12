<?php

namespace App\Policies;

use App\Models\EnterpriseHealthScore;
use App\Models\User;

class EnterpriseHealthScorePolicy
{
    public function viewAny(User $user): bool
    {
        return auth()->check();
    }

    public function view(User $user, EnterpriseHealthScore $score): bool
    {
        return $user->organizations()->where('organizations.id', $score->organization_id)->exists();
    }

    public function create(User $user): bool
    {
        return false; // Created only by the Digital Immune System
    }

    public function update(User $user, EnterpriseHealthScore $score): bool
    {
        return false; // System-managed
    }

    public function delete(User $user, EnterpriseHealthScore $score): bool
    {
        return $user->hasRole('super-admin');
    }
}
