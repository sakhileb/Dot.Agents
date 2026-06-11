<?php

namespace App\Policies;

use App\Models\SocialPost;
use App\Models\User;

class SocialPostPolicy
{
    public function viewAny(User $user): bool
    {
        return auth()->check();
    }

    public function view(User $user, SocialPost $post): bool
    {
        return $user->organizations()->where('organizations.id', $post->organization_id)->exists();
    }

    public function create(User $user, int $organizationId): bool
    {
        return $user->organizations()->where('organizations.id', $organizationId)->exists();
    }

    public function update(User $user, SocialPost $post): bool
    {
        return $user->organizations()
            ->where('organizations.id', $post->organization_id)
            ->exists();
    }

    public function approve(User $user, SocialPost $post): bool
    {
        return $user->organizations()
            ->where('organizations.id', $post->organization_id)
            ->wherePivotIn('role', ['owner', 'admin'])
            ->exists();
    }

    public function delete(User $user, SocialPost $post): bool
    {
        return $post->status !== 'published' &&
            $user->organizations()
                ->where('organizations.id', $post->organization_id)
                ->wherePivotIn('role', ['owner', 'admin'])
                ->exists();
    }
}
