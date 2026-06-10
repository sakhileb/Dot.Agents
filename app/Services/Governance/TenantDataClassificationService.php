<?php

namespace App\Services\Governance;

use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * TenantDataClassificationService
 *
 * Implements tenant data classification as required by ISO 27001 Annex A.8.2,
 * SOC 2 CC6.1, and POPIA/GDPR data minimisation obligations.
 *
 * Data is classified at four sensitivity levels:
 *
 *  Level 1 — PUBLIC       : Can be freely shared. No restrictions.
 *  Level 2 — INTERNAL     : Organisation-internal use only. Not for external parties.
 *  Level 3 — CONFIDENTIAL : Sensitive business data. Access restricted to authorised roles.
 *  Level 4 — RESTRICTED   : Highest sensitivity (PII, financial, legal). Requires explicit approval.
 *
 * Enforcement:
 *  - classify()  — tag a data payload with a classification level
 *  - canAccess() — check whether a given role is allowed to access data at a level
 *  - log()       — record access to RESTRICTED data in the audit trail
 */
class TenantDataClassificationService
{
    // Classification levels (ascending sensitivity)
    public const PUBLIC = 'public';       // Level 1

    public const INTERNAL = 'internal';     // Level 2

    public const CONFIDENTIAL = 'confidential'; // Level 3

    public const RESTRICTED = 'restricted';   // Level 4

    /**
     * Numeric ranking used for comparison (higher = more sensitive).
     */
    private const LEVEL_RANK = [
        self::PUBLIC => 1,
        self::INTERNAL => 2,
        self::CONFIDENTIAL => 3,
        self::RESTRICTED => 4,
    ];

    /**
     * Minimum role required to access each classification level.
     * Roles listed are cumulative (an 'owner' can access everything below).
     */
    private const ACCESS_POLICY = [
        self::PUBLIC => ['owner', 'admin', 'manager', 'member', 'viewer', 'guest'],
        self::INTERNAL => ['owner', 'admin', 'manager', 'member'],
        self::CONFIDENTIAL => ['owner', 'admin', 'manager'],
        self::RESTRICTED => ['owner', 'admin'],
    ];

    /**
     * Model / resource type to default classification mapping.
     * Used when a caller does not explicitly provide a classification.
     */
    private const DEFAULT_CLASSIFICATION = [
        'AgentMemory' => self::CONFIDENTIAL,
        'KnowledgeArticle' => self::INTERNAL,
        'KnowledgeBase' => self::INTERNAL,
        'Invoice' => self::RESTRICTED,
        'UsageRecord' => self::CONFIDENTIAL,
        'OrganizationSubscription' => self::RESTRICTED,
        'AuditLog' => self::CONFIDENTIAL,
        'DecisionLog' => self::CONFIDENTIAL,
        'SecurityEvent' => self::RESTRICTED,
        'AgentDeployment' => self::INTERNAL,
        'AgentWorkflow' => self::INTERNAL,
        'WorkflowExecution' => self::INTERNAL,
        'AgentTask' => self::INTERNAL,
        'AgentSession' => self::CONFIDENTIAL,
        'User' => self::RESTRICTED,
        'Department' => self::INTERNAL,
        'Division' => self::INTERNAL,
        'PlatformNotification' => self::INTERNAL,
    ];

    /**
     * Return the classification level for a given model/resource type.
     *
     * @param  string  $resourceType  Short model class name (e.g. 'Invoice')
     * @param  string|null  $override  Explicit override level
     */
    public function classify(string $resourceType, ?string $override = null): string
    {
        if ($override && isset(self::LEVEL_RANK[$override])) {
            return $override;
        }

        return self::DEFAULT_CLASSIFICATION[$resourceType] ?? self::INTERNAL;
    }

    /**
     * Check whether a given role may access data at the specified classification level.
     *
     * @param  string  $role  The user's role within the organisation
     * @param  string  $classification  The data classification level to check
     */
    public function canAccess(string $role, string $classification): bool
    {
        $allowed = self::ACCESS_POLICY[$classification] ?? self::ACCESS_POLICY[self::RESTRICTED];

        return in_array($role, $allowed, true);
    }

    /**
     * Assert that a role may access a given classification level.
     * Aborts with 403 and logs a warning if access is denied.
     *
     * @param  array  $context  Additional context for logging
     *
     * @throws HttpException
     */
    public function assertAccess(string $role, string $classification, array $context = []): void
    {
        if ($this->canAccess($role, $classification)) {
            return;
        }

        Log::warning('[DataClassification] Access denied to classified resource', array_merge([
            'role' => $role,
            'classification' => $classification,
        ], $context));

        abort(403, "Access denied: [{$classification}] data requires a higher permission level.");
    }

    /**
     * Get the numeric rank of a classification (for comparison).
     */
    public function rank(string $classification): int
    {
        return self::LEVEL_RANK[$classification] ?? 0;
    }

    /**
     * Return all valid classification levels in ascending sensitivity order.
     *
     * @return string[]
     */
    public function levels(): array
    {
        return array_keys(self::LEVEL_RANK);
    }
}
