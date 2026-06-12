<?php

namespace App\Policies;

use App\Models\TeamInvitation;
use App\Models\User;

class TeamInvitationPolicy
{
    public function viewAny(User $user): bool
    {
        return auth()->check();
    }

    public function view(User $user, TeamInvitation $invitation): bool
    {
        // Invitee can see their own invitation; org owner/admin can see all
        if ($invitation->email === $user->email) {
            return true;
        }

        return $user->organizations()
            ->where('organizations.id', $invitation->team_id)
            ->wherePivotIn('role', ['owner', 'admin'])
            ->exists();
    }

    public function create(User $user): bool
    {
        return auth()->check();
    }

    public function update(User $user, TeamInvitation $invitation): bool
    {
        return $user->organizations()
            ->where('organizations.id', $invitation->team_id)
            ->wherePivotIn('role', ['owner', 'admin'])
            ->exists();
    }

    public function delete(User $user, TeamInvitation $invitation): bool
    {
        // Invitee can cancel their own; org owner/admin can revoke
        if ($invitation->email === $user->email) {
            return true;
        }

        return $user->organizations()
            ->where('organizations.id', $invitation->team_id)
            ->wherePivotIn('role', ['owner', 'admin'])
            ->exists();
    }
}
