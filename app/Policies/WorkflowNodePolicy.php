<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkflowNode;

class WorkflowNodePolicy
{
    public function viewAny(User $user): bool
    {
        return auth()->check();
    }

    public function view(User $user, WorkflowNode $node): bool
    {
        return $user->organizations()->where('organizations.id', $node->organization_id)->exists();
    }

    public function create(User $user): bool
    {
        return auth()->check();
    }

    public function update(User $user, WorkflowNode $node): bool
    {
        return $user->organizations()
            ->where('organizations.id', $node->organization_id)
            ->wherePivotIn('role', ['owner', 'admin'])
            ->exists();
    }

    public function delete(User $user, WorkflowNode $node): bool
    {
        return $user->organizations()
            ->where('organizations.id', $node->organization_id)
            ->wherePivotIn('role', ['owner', 'admin'])
            ->exists();
    }
}
