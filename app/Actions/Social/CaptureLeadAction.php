<?php

namespace App\Actions\Social;

use App\DTOs\Social\CaptureLeadData;
use App\Events\PurchaseIntentDetected;
use App\Events\SocialLeadCaptured;
use App\Models\SocialConversation;
use App\Models\SocialLead;
use App\Services\Governance\AuditService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class CaptureLeadAction
{
    public function __construct(
        private readonly AuditService $auditService,
    ) {}

    public function execute(CaptureLeadData $data): SocialLead
    {
        Gate::authorize('create', [SocialLead::class, $data->organizationId]);

        // Prevent duplicate leads per platform contact within the same org
        $lead = SocialLead::withoutGlobalScope('organization')
            ->where('organization_id', $data->organizationId)
            ->where('platform', $data->platform)
            ->where('contact_platform_id', $data->contactPlatformId)
            ->first();

        if ($lead) {
            // Update with latest data and score
            $lead->update([
                'intent_level' => $data->intentLevel,
                'lead_score' => max($lead->lead_score, $data->leadScore),
                'intent_score' => max($lead->intent_score, $data->intentScore),
                'recommended_actions' => $data->recommendedActions,
                'last_touch_at' => now(),
            ]);
        } else {
            $lead = SocialLead::create([
                ...$data->toArray(),
                'uuid' => (string) Str::uuid(),
                'status' => 'new',
                'stage' => 'awareness',
                'first_touch_at' => now(),
                'last_touch_at' => now(),
            ]);

            event(new SocialLeadCaptured($lead));
        }

        // Mark conversation as a lead
        if ($data->socialConversationId) {
            SocialConversation::withoutGlobalScope('organization')
                ->where('id', $data->socialConversationId)
                ->update([
                    'is_lead' => true,
                    'intent' => $data->intentLevel,
                    'intent_score' => $data->intentScore,
                ]);
        }

        // Fire intent detection event for high-intent leads
        if (in_array($data->intentLevel, ['ready_to_buy', 'high_intent']) || $data->intentScore >= 75) {
            $conversation = $data->socialConversationId
                ? SocialConversation::find($data->socialConversationId)
                : null;

            if ($conversation) {
                event(new PurchaseIntentDetected($conversation, $data->intentScore, $data->intentLevel));
            }
        }

        $this->auditService->logUserAction(
            event: 'social_lead.captured',
            description: "Social lead captured from {$data->platform}",
            data: [
                'platform' => $data->platform,
                'intent_level' => $data->intentLevel,
                'lead_score' => $data->leadScore,
            ],
            subject: $lead,
        );

        return $lead;
    }
}
