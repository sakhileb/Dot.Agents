<?php

namespace Tests\Unit\DTOs;

use App\DTOs\Agents\RateAgentTaskData;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Unit tests for RateAgentTaskData DTO.
 *
 * Validates the rating guard (1–5), fromArray hydration, and toArray output.
 */
class RateAgentTaskDataTest extends TestCase
{
    public function test_valid_rating_constructs_successfully(): void
    {
        foreach (range(1, 5) as $rating) {
            $dto = new RateAgentTaskData(taskId: 1, rating: $rating);
            $this->assertSame($rating, $dto->rating);
        }
    }

    public function test_rating_below_1_throws_validation_exception(): void
    {
        $this->expectException(ValidationException::class);

        new RateAgentTaskData(taskId: 1, rating: 0);
    }

    public function test_rating_above_5_throws_validation_exception(): void
    {
        $this->expectException(ValidationException::class);

        new RateAgentTaskData(taskId: 1, rating: 6);
    }

    public function test_feedback_defaults_to_null(): void
    {
        $dto = new RateAgentTaskData(taskId: 1, rating: 3);

        $this->assertNull($dto->feedback);
    }

    public function test_from_array_hydrates_all_fields(): void
    {
        $dto = RateAgentTaskData::fromArray([
            'task_id' => '7',
            'rating' => '4',
            'feedback' => 'Great work!',
        ]);

        $this->assertSame(7, $dto->taskId);
        $this->assertSame(4, $dto->rating);
        $this->assertSame('Great work!', $dto->feedback);
    }

    public function test_from_array_feedback_defaults_to_null(): void
    {
        $dto = RateAgentTaskData::fromArray([
            'task_id' => '1',
            'rating' => '5',
        ]);

        $this->assertNull($dto->feedback);
    }

    public function test_to_array_includes_all_keys(): void
    {
        $dto = new RateAgentTaskData(taskId: 2, rating: 4, feedback: 'Nice');
        $array = $dto->toArray();

        $this->assertArrayHasKey('task_id', $array);
        $this->assertArrayHasKey('rating', $array);
        $this->assertArrayHasKey('feedback', $array);
        $this->assertSame(2, $array['task_id']);
        $this->assertSame(4, $array['rating']);
        $this->assertSame('Nice', $array['feedback']);
    }
}
