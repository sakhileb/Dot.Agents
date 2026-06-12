<?php

namespace App\Policies;

use App\Models\OrganizationTwin;
use App\Models\User;

class OrganizationTwinPolicy
{
    public function viewAny(User $user): bool
    {
        return auth()->check();
    }

    public function view(User $user, OrganizationTwin $twin): bool
    {
        return $user->organizations()->where('organizations.id', $twin->organization_id)->exists();
    }

    public function create(User $user): bool
    {
        return false; // Created only by the Digital Twin System
    }

    public function update(User $user, OrganizationTwin $twin): bool
    {
        return $user->organizations()
            ->where('organizations.id', $twin->organization_id)
            ->wherePivotIn('role', ['owner', 'admin'])
            ->exists();
    }

    public function delete(User $user, OrganizationTwin $twin): bool
    {
        return $user->hasRole('super-admin');
    }
}
