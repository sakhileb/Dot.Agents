<?php

namespace App\Policies;

use App\Models\PlatformMegaScorecard;
use App\Models\User;

class PlatformMegaScorecardPolicy
{
    public function viewAny(User $user): bool
    {
        return auth()->check();
    }

    public function view(User $user, PlatformMegaScorecard $scorecard): bool
    {
        return $user->organizations()->where('organizations.id', $scorecard->organization_id)->exists();
    }

    public function create(User $user): bool
    {
        return false; // Created only by the ScorecardService system job
    }

    public function update(User $user, PlatformMegaScorecard $scorecard): bool
    {
        return false; // System-managed
    }

    public function delete(User $user, PlatformMegaScorecard $scorecard): bool
    {
        return $user->hasRole('super-admin');
    }
}
