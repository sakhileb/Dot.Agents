<?php

namespace App\Policies;

use App\Models\Department;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DepartmentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->organizations()->where('organizations.id', session('current_organization_id'))->exists();
    }

    public function view(User $user, Department $department): bool
    {
        return $department->organization_id === (int) session('current_organization_id');
    }

    public function create(User $user): bool
    {
        $orgId = (int) session('current_organization_id');

        return $user->organizations()
            ->where('organizations.id', $orgId)
            ->wherePivotIn('role', ['owner', 'admin'])
            ->exists();
    }

    public function update(User $user, Department $department): bool
    {
        $orgId = (int) session('current_organization_id');

        return $department->organization_id === $orgId
            && $user->organizations()
                ->where('organizations.id', $orgId)
                ->wherePivotIn('role', ['owner', 'admin'])
                ->exists();
    }

    public function delete(User $user, Department $department): bool
    {
        $orgId = (int) session('current_organization_id');

        return $department->organization_id === $orgId
            && $user->organizations()
                ->where('organizations.id', $orgId)
                ->wherePivotIn('role', ['owner'])
                ->exists();
    }
}
