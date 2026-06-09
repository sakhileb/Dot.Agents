<?php

namespace App\Services\AI;

use App\Models\AgentDeployment;
use Illuminate\Support\Facades\Log;

/**
 * Enforces execution boundaries for an AI agent during task processing.
 *
 * Sandboxing boundaries enforced:
 *  1. Tenant isolation — prevents cross-org data access
 *  2. Permission scope  — validates requested action against allowed_actions
 *  3. Memory isolation  — agent can only access its own deployment memory
 *  4. Tool restrictions — validates tool use against deployment config
 *  5. Token budget      — prevents runaway token spend per task
 */
class AgentSandboxService
{
    private const MAX_TOKENS_PER_TASK = 32000;

    private const MAX_TOOL_CALLS_PER_TASK = 50;

    /**
     * Validate that an agent action is permitted within its sandbox.
     *
     * @throws \RuntimeException if the action violates sandbox rules
     */
    public function assertPermitted(
        AgentDeployment $deployment,
        string $action,
        array $context = [],
    ): void {
        $this->enforceTenantIsolation($deployment, $context);
        $this->enforceActionPermission($deployment, $action);
        $this->enforceToolRestrictions($deployment, $action, $context);
    }

    /**
     * Check whether a token budget is within the per-task limit.
     *
     * @throws \RuntimeException if the token budget is exceeded
     */
    public function enforceTokenBudget(AgentDeployment $deployment, int $tokensUsed): void
    {
        $limit = $deployment->agent?->model_config['max_tokens_per_task'] ?? self::MAX_TOKENS_PER_TASK;

        if ($tokensUsed > $limit) {
            Log::warning('AgentSandboxService: token budget exceeded', [
                'deployment_id' => $deployment->id,
                'tokens_used' => $tokensUsed,
                'limit' => $limit,
            ]);

            throw new \RuntimeException(
                "Token budget exceeded for deployment [{$deployment->id}]. Used: {$tokensUsed}, Limit: {$limit}."
            );
        }
    }

    /**
     * Validate a memory key belongs to this deployment (tenant/memory isolation).
     *
     * @throws \RuntimeException if the key belongs to another deployment
     */
    public function assertMemoryOwnership(AgentDeployment $deployment, string $memoryKey): void
    {
        // Memory keys are namespaced: "deployment:{id}:{key}"
        if (! str_starts_with($memoryKey, "deployment:{$deployment->id}:")) {
            Log::error('AgentSandboxService: memory isolation violation', [
                'deployment_id' => $deployment->id,
                'memory_key' => $memoryKey,
            ]);

            throw new \RuntimeException(
                "Memory isolation violation: deployment [{$deployment->id}] cannot access key [{$memoryKey}]."
            );
        }
    }

    /**
     * Return a sandboxed memory key for this deployment.
     */
    public function namespaceMemoryKey(AgentDeployment $deployment, string $key): string
    {
        return "deployment:{$deployment->id}:{$key}";
    }

    // ─── Private enforcement methods ────────────────────────────────────────────

    private function enforceTenantIsolation(AgentDeployment $deployment, array $context): void
    {
        $contextOrgId = $context['organization_id'] ?? null;

        if ($contextOrgId !== null && (int) $contextOrgId !== $deployment->organization_id) {
            Log::critical('AgentSandboxService: tenant isolation violation', [
                'deployment_id' => $deployment->id,
                'deployment_org' => $deployment->organization_id,
                'context_org' => $contextOrgId,
            ]);

            throw new \RuntimeException(
                "Tenant isolation violation: deployment [{$deployment->id}] tried to access org [{$contextOrgId}]."
            );
        }
    }

    private function enforceActionPermission(AgentDeployment $deployment, string $action): void
    {
        $allowedActions = $deployment->allowed_actions ?? [];
        $restrictedActions = $deployment->restricted_actions ?? [];

        if (in_array($action, $restrictedActions, true)) {
            Log::warning('AgentSandboxService: restricted action blocked', [
                'deployment_id' => $deployment->id,
                'action' => $action,
            ]);

            throw new \RuntimeException(
                "Action [{$action}] is explicitly restricted for deployment [{$deployment->id}]."
            );
        }

        if (! empty($allowedActions) && ! in_array($action, $allowedActions, true)) {
            Log::warning('AgentSandboxService: action not in allowlist', [
                'deployment_id' => $deployment->id,
                'action' => $action,
            ]);

            throw new \RuntimeException(
                "Action [{$action}] is not in the allowlist for deployment [{$deployment->id}]."
            );
        }
    }

    private function enforceToolRestrictions(
        AgentDeployment $deployment,
        string $action,
        array $context,
    ): void {
        $tool = $context['tool'] ?? null;

        if (! $tool) {
            return;
        }

        $agentTools = $deployment->agent?->tools ?? [];

        if (! in_array($tool, $agentTools, true)) {
            Log::warning('AgentSandboxService: tool not registered for agent', [
                'deployment_id' => $deployment->id,
                'tool' => $tool,
            ]);

            throw new \RuntimeException(
                "Tool [{$tool}] is not registered for agent in deployment [{$deployment->id}]."
            );
        }
    }
}
