<?php

declare(strict_types=1);

namespace App\DTOs\Workflows;

readonly class DeleteWorkflowData
{
    public function __construct(
        public int $workflowId,
    ) {}

    public static function fromId(int $workflowId): self
    {
        return new self(workflowId: $workflowId);
    }
}
