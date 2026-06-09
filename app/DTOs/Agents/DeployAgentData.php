<?php

namespace App\DTOs\Agents;

readonly class DeployAgentData
{
    public function __construct(
        public int $agentId,
        public int $organizationId,
        public int $deployedBy,
        public string $name,
        public string $deploymentMode,
        public float $confidenceThreshold = 75.0,
        public ?int $departmentId = null,
        public ?string $customInstructions = null,
        public bool $requiresHumanApproval = true,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            agentId: (int) $data['agent_id'],
            organizationId: (int) $data['organization_id'],
            deployedBy: (int) $data['deployed_by'],
            name: $data['name'],
            deploymentMode: $data['deployment_mode'],
            confidenceThreshold: (float) ($data['confidence_threshold'] ?? 75.0),
            departmentId: isset($data['department_id']) ? (int) $data['department_id'] : null,
            customInstructions: $data['custom_instructions'] ?? null,
            requiresHumanApproval: $data['deployment_mode'] !== 'autonomous',
        );
    }

    public function toArray(): array
    {
        return [
            'agent_id' => $this->agentId,
            'organization_id' => $this->organizationId,
            'deployed_by' => $this->deployedBy,
            'name' => $this->name,
            'deployment_mode' => $this->deploymentMode,
            'confidence_threshold' => $this->confidenceThreshold,
            'department_id' => $this->departmentId,
            'custom_instructions' => $this->customInstructions,
            'requires_human_approval' => $this->requiresHumanApproval,
        ];
    }
}
