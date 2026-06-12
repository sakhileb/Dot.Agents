<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Global scope for AgentPlugin tenant isolation.
 *
 * Plugins are different from other org-owned models: a plugin can be
 * platform-wide (organization_id IS NULL) or belong to a specific org.
 * Standard HasOrganizationScope would hide platform-wide plugins entirely,
 * so we apply a compound WHERE:
 *
 *   WHERE (organization_id IS NULL OR organization_id = ?)
 *
 * This scope only activates when there is an active org context in the
 * session, keeping admin/artisan/CLI contexts unrestricted.
 */
class PluginOrganizationScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $orgId = session('current_organization_id');

        if ($orgId) {
            $builder->where(function (Builder $q) use ($orgId): void {
                $q->whereNull('organization_id')
                    ->orWhere('organization_id', (int) $orgId);
            });
        }
    }
}
