<?php

namespace App\DTOs\Skills;

readonly class SkillPolicyContext
{
    public function __construct(
        public int $organizationId,
        public int $userId,
        public ?int $departmentId,
        public array $userPermissions,
        public array $organizationPolicies,
        public array $departmentPolicies,
        public float $budgetRemaining,
        public bool $isComplianceMode,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            organizationId: (int) $data['organization_id'],
            userId: (int) $data['user_id'],
            departmentId: isset($data['department_id']) ? (int) $data['department_id'] : null,
            userPermissions: $data['user_permissions'] ?? [],
            organizationPolicies: $data['organization_policies'] ?? [],
            departmentPolicies: $data['department_policies'] ?? [],
            budgetRemaining: (float) ($data['budget_remaining'] ?? PHP_FLOAT_MAX),
            isComplianceMode: (bool) ($data['is_compliance_mode'] ?? false),
        );
    }
}
