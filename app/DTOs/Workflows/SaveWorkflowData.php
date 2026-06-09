<?php

namespace App\DTOs\Workflows;

readonly class SaveWorkflowData
{
    public function __construct(
        public int $workflowId,
        public int $savedBy,
        public array $nodes,
        public array $connections,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            workflowId: (int) $data['workflow_id'],
            savedBy: (int) $data['saved_by'],
            nodes: $data['nodes'] ?? [],
            connections: $data['connections'] ?? [],
        );
    }
}
