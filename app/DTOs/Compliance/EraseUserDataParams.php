<?php

namespace App\DTOs\Compliance;

readonly class EraseUserDataParams
{
    public function __construct(
        public int $requesterId,
        public int $subjectId,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            requesterId: (int) $data['requester_id'],
            subjectId: (int) $data['subject_id'],
        );
    }

    public function toArray(): array
    {
        return [
            'requester_id' => $this->requesterId,
            'subject_id' => $this->subjectId,
        ];
    }
}
