<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlatformNotificationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'user_id' => User::factory(),
            'type' => $this->faker->randomElement(['info', 'warning', 'error', 'success', 'security_threat']),
            'title' => $this->faker->sentence(5),
            'message' => $this->faker->sentence(10),
            'severity' => $this->faker->randomElement(['info', 'warning', 'error', 'critical']),
            'is_read' => false,
            'read_at' => null,
            'data' => [],
            'action_url' => null,
        ];
    }

    public function read(): static
    {
        return $this->state(['is_read' => true, 'read_at' => now()]);
    }

    public function critical(): static
    {
        return $this->state(['severity' => 'critical', 'type' => 'security_threat']);
    }
}
