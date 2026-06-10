<?php

namespace App\Policies;

use App\Models\Division;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DivisionPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->organizations()->where('organizations.id', session('current_organization_id'))->exists();
    }

    public function view(User $user, Division $division): bool
    {
        return $division->organization_id === (int) session('current_organization_id');
    }

    public function create(User $user): bool
    {
        $orgId = (int) session('current_organization_id');

        return $user->organizations()
            ->where('organizations.id', $orgId)
            ->wherePivotIn('role', ['owner', 'admin'])
            ->exists();
    }

    public function update(User $user, Division $division): bool
    {
        $orgId = (int) session('current_organization_id');

        return $division->organization_id === $orgId
            && $user->organizations()
                ->where('organizations.id', $orgId)
                ->wherePivotIn('role', ['owner', 'admin'])
                ->exists();
    }

    public function delete(User $user, Division $division): bool
    {
        $orgId = (int) session('current_organization_id');

        return $division->organization_id === $orgId
            && $user->organizations()
                ->where('organizations.id', $orgId)
                ->wherePivotIn('role', ['owner'])
                ->exists();
    }
}
