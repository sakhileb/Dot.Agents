<?php

namespace App\Policies;

use App\Models\OrganizationSubscription;
use App\Models\User;

class OrganizationSubscriptionPolicy
{
    public function viewAny(User $user): bool
    {
        return auth()->check();
    }

    public function view(User $user, OrganizationSubscription $subscription): bool
    {
        return $user->organizations()->where('organizations.id', $subscription->organization_id)->exists();
    }

    public function create(User $user): bool
    {
        return auth()->check();
    }

    public function update(User $user, OrganizationSubscription $subscription): bool
    {
        return $user->organizations()
            ->where('organizations.id', $subscription->organization_id)
            ->wherePivot('role', 'owner')
            ->exists();
    }

    public function delete(User $user, OrganizationSubscription $subscription): bool
    {
        return $user->hasRole('super-admin');
    }
}
