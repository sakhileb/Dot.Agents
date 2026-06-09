<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AgentDeployment;
use App\Models\Organization;
use App\Models\SecurityEvent;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SecurityEventFactory extends Factory
{
    protected $model = SecurityEvent::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'organization_id' => Organization::factory(),
            'agent_deployment_id' => AgentDeployment::factory(),
            'event_type' => $this->faker->randomElement(['prompt_injection', 'unauthorized_access', 'data_leak_attempt', 'policy_violation']),
            'severity' => $this->faker->randomElement(['low', 'medium', 'high', 'critical']),
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->sentence(),
            'status' => 'open',
        ];
    }

    public function resolved(): self
    {
        return $this->state(['status' => 'resolved', 'resolved_at' => now()]);
    }
}
