<?php

namespace App\DTOs\Skills;

readonly class AssignSkillData
{
    public function __construct(
        public int $skillId,
        public int $agentDeploymentId,
        public int $organizationId,
        public bool $isEnabled = true,
        public array $config = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            skillId: (int) $data['skill_id'],
            agentDeploymentId: (int) $data['agent_deployment_id'],
            organizationId: (int) $data['organization_id'],
            isEnabled: (bool) ($data['is_enabled'] ?? true),
            config: $data['config'] ?? [],
        );
    }
}
