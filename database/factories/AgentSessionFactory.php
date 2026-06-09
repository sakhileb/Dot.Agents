<?php

namespace Database\Factories;

use App\Models\AgentDeployment;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AgentSessionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'agent_deployment_id' => AgentDeployment::factory(),
            'organization_id' => Organization::factory(),
            'user_id' => User::factory(),
            'session_type' => 'chat',
            'title' => $this->faker->sentence(4),
            'status' => 'active',
            'context' => [],
            'metadata' => [],
            'message_count' => 0,
            'token_count' => 0,
            'cost' => 0,
            'started_at' => now(),
            'ended_at' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state([
            'status' => 'completed',
            'ended_at' => now(),
            'message_count' => $this->faker->numberBetween(2, 20),
            'token_count' => $this->faker->numberBetween(100, 4000),
        ]);
    }
}
