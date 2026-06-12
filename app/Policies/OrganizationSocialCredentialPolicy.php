<?php

namespace App\Policies;

use App\Models\OrganizationSocialCredential;
use App\Models\User;

class OrganizationSocialCredentialPolicy
{
    public function viewAny(User $user): bool
    {
        return auth()->check();
    }

    public function view(User $user, OrganizationSocialCredential $credential): bool
    {
        return $user->organizations()->where('organizations.id', $credential->organization_id)->exists();
    }

    public function create(User $user): bool
    {
        return auth()->check();
    }

    public function update(User $user, OrganizationSocialCredential $credential): bool
    {
        return $user->organizations()
            ->where('organizations.id', $credential->organization_id)
            ->wherePivotIn('role', ['owner', 'admin'])
            ->exists();
    }

    public function delete(User $user, OrganizationSocialCredential $credential): bool
    {
        return $user->organizations()
            ->where('organizations.id', $credential->organization_id)
            ->wherePivotIn('role', ['owner', 'admin'])
            ->exists();
    }
}
