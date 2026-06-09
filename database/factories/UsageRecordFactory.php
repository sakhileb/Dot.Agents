<?php

namespace Database\Factories;

use App\Models\AgentDeployment;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

class UsageRecordFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'agent_deployment_id' => AgentDeployment::factory(),
            'user_id' => null,
            'record_type' => $this->faker->randomElement(['message', 'task', 'api_call']),
            'recorded_date' => now()->toDateString(),
            'message_count' => $this->faker->numberBetween(0, 50),
            'task_count' => $this->faker->numberBetween(0, 20),
            'token_count' => $this->faker->numberBetween(100, 10000),
            'input_tokens' => $this->faker->numberBetween(50, 5000),
            'output_tokens' => $this->faker->numberBetween(50, 5000),
            'compute_units' => $this->faker->randomFloat(4, 0.01, 10.0),
            'total_cost' => $this->faker->randomFloat(4, 0.001, 5.0),
            'currency' => 'USD',
            'metadata' => [],
        ];
    }
}
