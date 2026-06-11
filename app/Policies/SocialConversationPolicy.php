<?php

namespace App\Policies;

use App\Models\SocialConversation;
use App\Models\User;

class SocialConversationPolicy
{
    public function viewAny(User $user): bool
    {
        return auth()->check();
    }

    public function view(User $user, SocialConversation $conversation): bool
    {
        return $user->organizations()->where('organizations.id', $conversation->organization_id)->exists();
    }

    public function create(User $user, int $organizationId): bool
    {
        return $user->organizations()->where('organizations.id', $organizationId)->exists();
    }

    public function update(User $user, SocialConversation $conversation): bool
    {
        return $user->organizations()->where('organizations.id', $conversation->organization_id)->exists();
    }

    public function update_by_organization(User $user, int $organizationId): bool
    {
        return $user->organizations()->where('organizations.id', $organizationId)->exists();
    }

    public function escalate(User $user, SocialConversation $conversation): bool
    {
        return $user->organizations()
            ->where('organizations.id', $conversation->organization_id)
            ->exists();
    }
}
