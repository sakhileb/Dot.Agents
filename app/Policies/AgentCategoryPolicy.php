<?php

namespace App\Policies;

use App\Models\AgentCategory;
use App\Models\User;

class AgentCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // Public catalogue
    }

    public function view(User $user, AgentCategory $category): bool
    {
        return true; // Public catalogue
    }

    public function create(User $user): bool
    {
        return $user->hasRole('super-admin');
    }

    public function update(User $user, AgentCategory $category): bool
    {
        return $user->hasRole('super-admin');
    }

    public function delete(User $user, AgentCategory $category): bool
    {
        return $user->hasRole('super-admin');
    }
}
