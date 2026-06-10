<?php

namespace App\Policies;

use App\Models\KnowledgeBase;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class KnowledgeBasePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->organizations()->where('organizations.id', session('current_organization_id'))->exists();
    }

    public function view(User $user, KnowledgeBase $knowledgeBase): bool
    {
        return $knowledgeBase->organization_id === (int) session('current_organization_id');
    }

    public function create(User $user): bool
    {
        $orgId = (int) session('current_organization_id');

        return $user->organizations()
            ->where('organizations.id', $orgId)
            ->wherePivotIn('role', ['owner', 'admin', 'manager'])
            ->exists();
    }

    public function update(User $user, KnowledgeBase $knowledgeBase): bool
    {
        $orgId = (int) session('current_organization_id');

        return $knowledgeBase->organization_id === $orgId
            && $user->organizations()
                ->where('organizations.id', $orgId)
                ->wherePivotIn('role', ['owner', 'admin', 'manager'])
                ->exists();
    }

    public function delete(User $user, KnowledgeBase $knowledgeBase): bool
    {
        $orgId = (int) session('current_organization_id');

        return $knowledgeBase->organization_id === $orgId
            && $user->organizations()
                ->where('organizations.id', $orgId)
                ->wherePivotIn('role', ['owner', 'admin'])
                ->exists();
    }
}
