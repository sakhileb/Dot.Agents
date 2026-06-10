<?php

namespace App\Models\Concerns;

use App\Models\Scopes\OrganizationScope;
use Illuminate\Database\Eloquent\Builder;

/**
 * Attaches the OrganizationScope global scope to every model that uses it.
 *
 * Usage:
 *   class AgentDeployment extends Model {
 *       use BelongsToOrganization;
 *   }
 *
 * To query across all orgs (platform-admin, reporting):
 *   AgentDeployment::withoutOrganizationScope()->get();
 */
trait BelongsToOrganization
{
    /**
     * Boot the trait — registers the global scope automatically.
     */
    protected static function bootBelongsToOrganization(): void
    {
        static::addGlobalScope(new OrganizationScope);
    }

    /**
     * Return a query builder with the organization scope removed.
     *
     * Useful for cross-tenant operations: admin dashboards, DIS scans,
     * background jobs that process all organizations.
     */
    public static function withoutOrganizationScope(): Builder
    {
        return static::withoutGlobalScope(OrganizationScope::class);
    }
}
