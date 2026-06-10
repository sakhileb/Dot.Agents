<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /** Platform admins and org owners can list users. */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['owner', 'admin', 'platform_admin']);
    }

    /** Users can view their own profile; org admins can view org members. */
    public function view(User $user, User $model): bool
    {
        return $user->id === $model->id
            || $user->hasAnyRole(['owner', 'admin', 'platform_admin']);
    }

    /** Registration is handled by Fortify; direct creation requires platform admin. */
    public function create(User $user): bool
    {
        return $user->hasRole('platform_admin');
    }

    /** Users can update their own profile; admins can update org members. */
    public function update(User $user, User $model): bool
    {
        return $user->id === $model->id
            || $user->hasAnyRole(['owner', 'admin', 'platform_admin']);
    }

    /** Users can delete their own account; platform admins can delete any user. */
    public function delete(User $user, User $model): bool
    {
        return $user->id === $model->id
            || $user->hasRole('platform_admin');
    }
}
