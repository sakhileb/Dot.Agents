<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\SocialLead;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SocialLead>
 */
class SocialLeadFactory extends Factory
{
    protected $model = SocialLead::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'organization_id' => Organization::factory(),
            'social_conversation_id' => null,
            'agent_deployment_id' => null,
            'platform' => fake()->randomElement(['facebook', 'instagram', 'linkedin', 'x', 'whatsapp']),
            'contact_platform_id' => fake()->numerify('lead_########'),
            'contact_name' => fake()->name(),
            'contact_handle' => '@'.fake()->userName(),
            'email' => null,
            'phone' => null,
            'company' => fake()->company(),
            'job_title' => fake()->jobTitle(),
            'location' => fake()->city(),
            'status' => 'new',
            'stage' => 'awareness',
            'intent_level' => 'browsing',
            'lead_score' => fake()->randomFloat(2, 0, 100),
            'intent_score' => fake()->randomFloat(2, 0, 100),
            'priority' => 'normal',
            'recommended_actions' => [],
            'crm_synced' => false,
            'crm_platform' => null,
            'crm_record_id' => null,
            'crm_synced_at' => null,
            'custom_fields' => [],
            'interaction_history' => [],
            'first_touch_at' => now(),
            'last_touch_at' => now(),
            'qualified_at' => null,
            'converted_at' => null,
        ];
    }
}
