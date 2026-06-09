<?php

namespace Database\Factories;

use App\Models\AgentDeployment;
use App\Models\AgentMemory;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AgentMemoryFactory extends Factory
{
    protected $model = AgentMemory::class;

    public function definition(): array
    {
        return [
            'uuid' => Str::uuid(),
            'agent_deployment_id' => AgentDeployment::factory(),
            'organization_id' => Organization::factory(),
            'user_id' => null,
            'memory_type' => $this->faker->randomElement(['episodic', 'semantic', 'procedural']),
            'memory_category' => $this->faker->randomElement(['general', 'preference', 'fact']),
            'subject' => $this->faker->sentence(4),
            'content' => $this->faker->paragraph(),
            'context' => [],
            'tags' => [],
            'importance_score' => $this->faker->randomFloat(2, 0, 1),
            'confidence_score' => $this->faker->randomFloat(2, 0, 1),
            'access_count' => 0,
            'last_accessed_at' => null,
            'expires_at' => null,
            'is_verified' => false,
            'is_active' => true,
        ];
    }
}
