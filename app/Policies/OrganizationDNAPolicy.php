<?php

namespace App\Policies;

use App\Models\OrganizationDNA;
use App\Models\User;

class OrganizationDNAPolicy
{
    public function viewAny(User $user): bool
    {
        return auth()->check();
    }

    public function view(User $user, OrganizationDNA $dna): bool
    {
        return $user->organizations()->where('organizations.id', $dna->organization_id)->exists();
    }

    public function create(User $user): bool
    {
        return auth()->check();
    }

    public function update(User $user, OrganizationDNA $dna): bool
    {
        return $user->organizations()
            ->where('organizations.id', $dna->organization_id)
            ->wherePivotIn('role', ['owner', 'admin'])
            ->exists();
    }

    public function delete(User $user, OrganizationDNA $dna): bool
    {
        return $user->organizations()
            ->where('organizations.id', $dna->organization_id)
            ->wherePivot('role', 'owner')
            ->exists();
    }
}
