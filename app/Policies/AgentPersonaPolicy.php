<?php

namespace App\Policies;

use App\Models\AgentPersona;
use App\Models\User;

class AgentPersonaPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // Public catalogue
    }

    public function view(User $user, AgentPersona $persona): bool
    {
        return true; // Public catalogue
    }

    public function create(User $user): bool
    {
        return $user->hasRole('super-admin');
    }

    public function update(User $user, AgentPersona $persona): bool
    {
        return $user->hasRole('super-admin');
    }

    public function delete(User $user, AgentPersona $persona): bool
    {
        return $user->hasRole('super-admin');
    }
}
