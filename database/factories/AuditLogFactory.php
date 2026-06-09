<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'organization_id' => Organization::factory(),
            'user_id' => User::factory(),
            'event' => $this->faker->randomElement(['agent.deployed', 'agent.task.completed', 'user.login', 'settings.updated']),
            'event_category' => $this->faker->randomElement(['agent_action', 'user_action', 'system_event', 'security_event']),
            'description' => $this->faker->sentence(),
            'risk_level' => $this->faker->randomElement(['low', 'medium', 'high', 'critical']),
            'flagged' => false,
        ];
    }
}
