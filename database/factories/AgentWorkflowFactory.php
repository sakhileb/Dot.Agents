<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AgentWorkflowFactory extends Factory
{
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'organization_id' => Organization::factory(),
            'created_by' => User::factory(),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'trigger_type' => $this->faker->randomElement(['manual', 'scheduled', 'event', 'api']),
            'trigger_config' => [],
            'steps' => [],
            'agents_involved' => [],
            'status' => 'draft',
            'is_template' => false,
        ];
    }

    public function active(): static
    {
        return $this->state(['status' => 'active']);
    }

    public function template(): static
    {
        return $this->state(['status' => 'active', 'is_template' => true]);
    }
}
