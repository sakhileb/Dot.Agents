<?php

namespace App\Policies;

use App\Models\SubscriptionPlan;
use App\Models\User;

class SubscriptionPlanPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // Public pricing catalogue
    }

    public function view(User $user, SubscriptionPlan $plan): bool
    {
        return true; // Public pricing catalogue
    }

    public function create(User $user): bool
    {
        return $user->hasRole('super-admin');
    }

    public function update(User $user, SubscriptionPlan $plan): bool
    {
        return $user->hasRole('super-admin');
    }

    public function delete(User $user, SubscriptionPlan $plan): bool
    {
        return $user->hasRole('super-admin');
    }
}
