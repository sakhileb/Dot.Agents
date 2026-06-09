<?php

namespace Database\Factories;

use App\Models\AgentCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AgentCategory>
 */
class AgentCategoryFactory extends Factory
{
    protected $model = AgentCategory::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => ucwords($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('###'),
            'description' => fake()->sentence(),
            'icon' => 'heroicon-o-cpu-chip',
            'color' => fake()->hexColor(),
            'sort_order' => fake()->numberBetween(1, 100),
            'is_active' => true,
        ];
    }
}
