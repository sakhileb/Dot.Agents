<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\SocialAccount;
use App\Models\SocialConversation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SocialConversation>
 */
class SocialConversationFactory extends Factory
{
    protected $model = SocialConversation::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'organization_id' => Organization::factory(),
            'social_account_id' => SocialAccount::factory(),
            'social_page_id' => null,
            'agent_deployment_id' => null,
            'assigned_user_id' => null,
            'platform_conversation_id' => fake()->numerify('conv_########'),
            'platform' => fake()->randomElement(['facebook', 'instagram', 'whatsapp', 'linkedin', 'x']),
            'channel_type' => fake()->randomElement(['dm', 'comment', 'mention']),
            'contact_platform_id' => fake()->numerify('contact_########'),
            'contact_name' => fake()->name(),
            'contact_handle' => '@'.fake()->userName(),
            'contact_avatar' => null,
            'status' => 'open',
            'priority' => 'normal',
            'sentiment' => 'neutral',
            'sentiment_score' => 50.0,
            'intent' => 'browsing',
            'intent_score' => 10.0,
            'requires_human' => false,
            'is_lead' => false,
            'is_escalated' => false,
            'escalated_to' => null,
            'escalated_at' => null,
            'first_response_at' => null,
            'last_message_at' => now(),
            'resolved_at' => null,
            'message_count' => 1,
            'response_time_seconds' => null,
            'tags' => [],
            'metadata' => [],
        ];
    }
}
