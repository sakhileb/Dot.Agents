<?php

namespace App\Actions\Social;

use App\Events\SocialConversionAchieved;
use App\Models\SocialConversion;
use App\Models\SocialLead;
use App\Services\Governance\AuditService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class RecordSocialConversionAction
{
    public function __construct(
        private readonly AuditService $auditService,
    ) {}

    public function execute(
        int $organizationId,
        string $conversionType,
        int $actorId,
        ?int $socialLeadId = null,
        ?int $socialConversationId = null,
        ?int $agentDeploymentId = null,
        ?int $campaignId = null,
        ?float $revenue = null,
        string $currency = 'USD',
        ?string $productOrService = null,
        float $agentAttributionScore = 0.0,
        array $attributionPath = [],
        array $metadata = [],
    ): SocialConversion {
        Gate::authorize('create', [SocialConversion::class, $organizationId]);

        $conversion = SocialConversion::create([
            'uuid' => (string) Str::uuid(),
            'organization_id' => $organizationId,
            'social_lead_id' => $socialLeadId,
            'social_conversation_id' => $socialConversationId,
            'agent_deployment_id' => $agentDeploymentId,
            'campaign_id' => $campaignId,
            'conversion_type' => $conversionType,
            'revenue' => $revenue,
            'currency' => $currency,
            'product_or_service' => $productOrService,
            'agent_attribution_score' => $agentAttributionScore,
            'attribution_path' => $attributionPath,
            'metadata' => $metadata,
            'converted_at' => now(),
        ]);

        // Mark lead as converted
        if ($socialLeadId) {
            SocialLead::withoutGlobalScope('organization')
                ->where('id', $socialLeadId)
                ->update(['status' => 'converted', 'converted_at' => now()]);
        }

        event(new SocialConversionAchieved($conversion));

        $this->auditService->logUserAction(
            event: 'social_conversion.recorded',
            description: "Social conversion recorded: {$conversionType}",
            data: [
                'conversion_type' => $conversionType,
                'revenue' => $revenue,
                'agent_attribution_score' => $agentAttributionScore,
            ],
            subject: $conversion,
        );

        return $conversion;
    }
}
