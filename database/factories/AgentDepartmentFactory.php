<?php

namespace Database\Factories;

use App\Models\AgentDepartment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AgentDepartment>
 */
class AgentDepartmentFactory extends Factory
{
    protected $model = AgentDepartment::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => ucwords($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('###'),
            'description' => fake()->sentence(),
            'icon' => 'heroicon-o-building-office',
            'color' => fake()->hexColor(),
            'sort_order' => fake()->numberBetween(1, 100),
            'is_active' => true,
        ];
    }
}
