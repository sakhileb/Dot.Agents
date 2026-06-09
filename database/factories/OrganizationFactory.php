<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Organization>
 */
class OrganizationFactory extends Factory
{
    protected $model = Organization::class;

    public function definition(): array
    {
        $name = fake()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('###'),
            'domain' => fake()->domainName(),
            'logo' => null,
            'industry' => fake()->randomElement([
                'Technology', 'Finance', 'Healthcare', 'Retail',
                'Manufacturing', 'Legal', 'Education', 'Consulting',
            ]),
            'size' => fake()->randomElement(['1-10', '11-50', '51-200', '201-500', '501+']),
            'country' => fake()->countryCode(),
            'timezone' => fake()->timezone(),
            'currency' => 'USD',
            'plan' => 'starter',
            'status' => 'active',
            'settings' => [],
            'billing_address' => null,
            'trial_ends_at' => now()->addDays(14),
            'subscription_ends_at' => null,
            'owner_id' => User::factory(),
        ];
    }

    public function active(): static
    {
        return $this->state(['status' => 'active']);
    }

    public function withSubscription(): static
    {
        return $this->state([
            'plan' => 'professional',
            'subscription_ends_at' => now()->addYear(),
            'trial_ends_at' => null,
        ]);
    }
}
