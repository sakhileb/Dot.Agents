<?php

namespace App\Policies;

use App\Models\SocialReview;
use App\Models\User;

class SocialReviewPolicy
{
    public function viewAny(User $user): bool
    {
        return auth()->check();
    }

    public function view(User $user, SocialReview $review): bool
    {
        return $user->organizations()->where('organizations.id', $review->organization_id)->exists();
    }

    public function create(User $user): bool
    {
        return false; // Created only by Social Commerce ingestion
    }

    public function update(User $user, SocialReview $review): bool
    {
        return $user->organizations()
            ->where('organizations.id', $review->organization_id)
            ->wherePivotIn('role', ['owner', 'admin'])
            ->exists();
    }

    public function delete(User $user, SocialReview $review): bool
    {
        return $user->hasRole('super-admin');
    }
}
