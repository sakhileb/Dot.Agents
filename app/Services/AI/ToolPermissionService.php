<?php

namespace App\Services\AI;

use App\Models\AgentDeployment;
use App\Models\AgentToolPermission;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Tool Permission Service
 *
 * Enforces fine-grained per-tool access control for AI agents.
 * Supports an org-wide default ruleset overlaid with per-deployment
 * overrides, following a deny-wins merge strategy:
 *
 *   1. Deployment-level DENY  → denied (highest priority)
 *   2. Org-level DENY         → denied
 *   3. Deployment-level ALLOW → allowed
 *   4. Org-level ALLOW        → allowed
 *   5. No rule found          → default = ALLOW (open by default)
 *
 * Role-scoped rules are evaluated if the requesting user has a matching role.
 */
class ToolPermissionService
{
    private const CACHE_TTL = 300; // 5-minute permission cache

    /**
     * Check if a deployment is permitted to use a specific tool.
     *
     * @throws \RuntimeException when tool use is denied
     */
    public function assertToolPermitted(
        AgentDeployment $deployment,
        string $toolName,
        ?string $userRole = null
    ): void {
        if (! $this->isToolPermitted($deployment, $toolName, $userRole)) {
            Log::warning('[ToolPermission] Tool access denied', [
                'deployment_id' => $deployment->id,
                'tool' => $toolName,
                'user_role' => $userRole,
                'organization_id' => $deployment->organization_id,
            ]);

            throw new \RuntimeException(
                "Tool [{$toolName}] is not permitted for deployment [{$deployment->id}]."
            );
        }
    }

    /**
     * Check (without throwing) if a tool is permitted.
     */
    public function isToolPermitted(
        AgentDeployment $deployment,
        string $toolName,
        ?string $userRole = null
    ): bool {
        $cacheKey = "tool_perm_{$deployment->id}_{$toolName}_".($userRole ?? 'any');

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($deployment, $toolName, $userRole) {
            return $this->evaluatePermission($deployment, $toolName, $userRole);
        });
    }

    /**
     * Get all permitted tools for a deployment (for UI display).
     */
    public function getPermittedTools(AgentDeployment $deployment, ?string $userRole = null): array
    {
        $orgRules = AgentToolPermission::where('organization_id', $deployment->organization_id)
            ->whereNull('agent_deployment_id')
            ->where('is_active', true)
            ->get();

        $deploymentRules = AgentToolPermission::where('agent_deployment_id', $deployment->id)
            ->where('is_active', true)
            ->get();

        $allowed = [];
        $denied = [];

        foreach ($orgRules->merge($deploymentRules) as $rule) {
            if ($rule->role_scope && $userRole && $rule->role_scope !== $userRole) {
                continue;
            }

            if ($rule->permission === 'deny') {
                $denied[] = $rule->tool_name;
            } else {
                $allowed[] = $rule->tool_name;
            }
        }

        return array_values(array_diff($allowed, $denied));
    }

    /**
     * Invalidate cached permissions for a deployment (call after rule changes).
     */
    public function invalidateCache(int $deploymentId): void
    {
        // Tags not available with file/array driver — clear all tool_perm_ keys
        Cache::flush(); // In production this should use Cache::tags(['tool_permissions'])
    }

    // ─── Private helpers ──────────────────────────────────────────────────────

    private function evaluatePermission(
        AgentDeployment $deployment,
        string $toolName,
        ?string $userRole
    ): bool {
        // Load deployment-specific rules (highest priority)
        $deploymentRules = AgentToolPermission::where('agent_deployment_id', $deployment->id)
            ->where('tool_name', $toolName)
            ->where('is_active', true)
            ->get();

        // Deployment DENY wins immediately
        foreach ($deploymentRules as $rule) {
            if ($this->ruleApplies($rule, $userRole) && $rule->permission === 'deny') {
                return false;
            }
        }

        // Load org-wide rules
        $orgRules = AgentToolPermission::where('organization_id', $deployment->organization_id)
            ->whereNull('agent_deployment_id')
            ->where('tool_name', $toolName)
            ->where('is_active', true)
            ->get();

        // Org DENY wins
        foreach ($orgRules as $rule) {
            if ($this->ruleApplies($rule, $userRole) && $rule->permission === 'deny') {
                return false;
            }
        }

        // Explicit ALLOW at deployment level
        foreach ($deploymentRules as $rule) {
            if ($this->ruleApplies($rule, $userRole) && $rule->permission === 'allow') {
                return true;
            }
        }

        // Explicit ALLOW at org level
        foreach ($orgRules as $rule) {
            if ($this->ruleApplies($rule, $userRole) && $rule->permission === 'allow') {
                return true;
            }
        }

        // No rule found → open by default (tools are allowed unless explicitly denied)
        return true;
    }

    private function ruleApplies(AgentToolPermission $rule, ?string $userRole): bool
    {
        // Rule without role scope applies to everyone
        if ($rule->role_scope === null) {
            return true;
        }

        return $userRole !== null && $rule->role_scope === $userRole;
    }
}
