<?php

namespace App\Policies;

use App\Models\AgentDepartment;
use App\Models\User;

class AgentDepartmentPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // Public catalogue
    }

    public function view(User $user, AgentDepartment $department): bool
    {
        return true; // Public catalogue
    }

    public function create(User $user): bool
    {
        return $user->hasRole('super-admin');
    }

    public function update(User $user, AgentDepartment $department): bool
    {
        return $user->hasRole('super-admin');
    }

    public function delete(User $user, AgentDepartment $department): bool
    {
        return $user->hasRole('super-admin');
    }
}
