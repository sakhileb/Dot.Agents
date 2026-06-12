<?php

namespace App\DTOs\Skills;

readonly class RecordSkillScoreData
{
    public function __construct(
        public int $skillId,
        public int $deploymentId,
        public int $organizationId,
        public string $executionStatus,
        public ?float $confidence = null,
        public ?int $durationMs = null,
    ) {
        if (! in_array($this->executionStatus, ['completed', 'failed', 'blocked', 'skipped'], true)) {
            throw new \InvalidArgumentException("Invalid execution status: {$this->executionStatus}.");
        }
    }

    public static function fromArray(array $data): self
    {
        return new self(
            skillId: (int) $data['skill_id'],
            deploymentId: (int) $data['deployment_id'],
            organizationId: (int) $data['organization_id'],
            executionStatus: $data['execution_status'],
            confidence: isset($data['confidence']) ? (float) $data['confidence'] : null,
            durationMs: isset($data['duration_ms']) ? (int) $data['duration_ms'] : null,
        );
    }

    public function toArray(): array
    {
        return [
            'skill_id' => $this->skillId,
            'deployment_id' => $this->deploymentId,
            'organization_id' => $this->organizationId,
            'execution_status' => $this->executionStatus,
            'confidence' => $this->confidence,
            'duration_ms' => $this->durationMs,
        ];
    }
}
