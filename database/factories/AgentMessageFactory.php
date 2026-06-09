<?php

namespace Database\Factories;

use App\Models\AgentSession;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AgentMessageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'session_id' => AgentSession::factory(),
            'role' => $this->faker->randomElement(['user', 'assistant']),
            'content' => $this->faker->paragraph(),
            'tool_calls' => null,
            'tool_results' => null,
            'metadata' => [],
            'token_count' => $this->faker->numberBetween(10, 500),
            'cost' => $this->faker->randomFloat(6, 0, 0.05),
            'model_used' => 'gpt-4o',
            'latency_ms' => $this->faker->numberBetween(200, 3000),
            'is_edited' => false,
            'flagged' => false,
            'flag_reason' => null,
        ];
    }

    public function userMessage(): static
    {
        return $this->state(['role' => 'user']);
    }

    public function assistantMessage(): static
    {
        return $this->state(['role' => 'assistant']);
    }
}
