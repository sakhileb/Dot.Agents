<?php

namespace App\DTOs\Skills;

readonly class ExecuteSkillData
{
    public function __construct(
        public int $skillId,
        public int $agentDeploymentId,
        public int $organizationId,
        public int $actorId,
        public string $trigger,             // on_demand|scheduled|delegated|pre_task|post_task
        public array $input = [],
        public ?int $taskId = null,
        public ?string $justification = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            skillId: (int) $data['skill_id'],
            agentDeploymentId: (int) $data['agent_deployment_id'],
            organizationId: (int) $data['organization_id'],
            actorId: (int) $data['actor_id'],
            trigger: $data['trigger'],
            input: $data['input'] ?? [],
            taskId: isset($data['task_id']) ? (int) $data['task_id'] : null,
            justification: $data['justification'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'skill_id' => $this->skillId,
            'agent_deployment_id' => $this->agentDeploymentId,
            'organization_id' => $this->organizationId,
            'actor_id' => $this->actorId,
            'trigger' => $this->trigger,
            'input' => $this->input,
            'task_id' => $this->taskId,
            'justification' => $this->justification,
        ];
    }
}
