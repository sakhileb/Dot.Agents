<?php

namespace App\DTOs\Agents;

use Illuminate\Validation\ValidationException;

readonly class RateAgentTaskData
{
    public function __construct(
        public int $taskId,
        public int $rating,
        public ?string $feedback = null,
    ) {
        if ($this->rating < 1 || $this->rating > 5) {
            throw ValidationException::withMessages([
                'rating' => 'Rating must be between 1 and 5.',
            ]);
        }
    }

    public static function fromArray(array $data): self
    {
        return new self(
            taskId: (int) $data['task_id'],
            rating: (int) $data['rating'],
            feedback: $data['feedback'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'task_id' => $this->taskId,
            'rating' => $this->rating,
            'feedback' => $this->feedback,
        ];
    }
}
