<?php

declare(strict_types=1);

namespace App\DTOs\Governance;

readonly class CreateDecisionLogData
{
    public function __construct(
        public int $agentDeploymentId,
        public int $agentTaskId,
        public array $output,
        public array $context = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            agentDeploymentId: (int) $data['agent_deployment_id'],
            agentTaskId: (int) $data['agent_task_id'],
            output: (array) $data['output'],
            context: (array) ($data['context'] ?? []),
        );
    }
}
