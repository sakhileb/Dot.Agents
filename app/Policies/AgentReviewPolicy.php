<?php

namespace App\Policies;

use App\Models\AgentReview;
use App\Models\User;

class AgentReviewPolicy
{
    public function viewAny(User $user): bool
    {
        return auth()->check();
    }

    public function view(User $user, AgentReview $review): bool
    {
        return $user->organizations()->where('organizations.id', $review->organization_id)->exists();
    }

    public function create(User $user): bool
    {
        return auth()->check();
    }

    public function update(User $user, AgentReview $review): bool
    {
        return $review->user_id === $user->id;
    }

    public function delete(User $user, AgentReview $review): bool
    {
        return $review->user_id === $user->id || $user->hasRole('super-admin');
    }
}
