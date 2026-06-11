<?php

namespace App\Policies;

use App\Models\SocialConversion;
use App\Models\User;

class SocialConversionPolicy
{
    public function viewAny(User $user): bool
    {
        return auth()->check();
    }

    public function view(User $user, SocialConversion $conversion): bool
    {
        return $user->organizations()->where('organizations.id', $conversion->organization_id)->exists();
    }

    public function create(User $user, int $organizationId): bool
    {
        return $user->organizations()->where('organizations.id', $organizationId)->exists();
    }
}
