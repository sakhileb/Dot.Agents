<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Automatically scopes all queries to the current organization context.
 *
 * Applied via the BelongsToOrganization trait on all tenant-owned models.
 * The scope is derived from the session (web) or the authenticated user's
 * current team (API/CLI contexts).
 *
 * To bypass for platform-admin or cross-tenant analytics:
 *   Model::withoutOrganizationScope()->get();
 */
class OrganizationScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * Only applies when an organization context is available.
     * Silent no-op in CLI/artisan contexts where no session exists,
     * so migrations, seeders, and scheduled jobs are unaffected.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $organizationId = $this->resolveOrganizationId();

        if ($organizationId) {
            $builder->where($model->getTable().'.organization_id', $organizationId);
        }
    }

    /**
     * Resolve the active organization ID from the current context.
     *
     * Priority:
     *  1. Session (web requests)
     *  2. Authenticated user's current team (API/Livewire requests)
     *  3. null  (CLI / migrations — scope is skipped)
     */
    private function resolveOrganizationId(): ?int
    {
        // CLI / queue / migration contexts — no HTTP request
        if (! app()->bound('request') || ! request()->hasSession()) {
            return null;
        }

        // Session context (standard web request after org switch)
        $fromSession = session('current_organization_id');
        if ($fromSession) {
            return (int) $fromSession;
        }

        // Fall back to authenticated user's current Jetstream team
        $user = auth()->user();
        if ($user && $user->currentTeam) {
            return (int) $user->currentTeam->id;
        }

        return null;
    }
}
