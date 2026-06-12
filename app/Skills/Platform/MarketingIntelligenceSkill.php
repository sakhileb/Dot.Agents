<?php

namespace App\Skills\Platform;

use App\Skills\DTOs\SkillResult;

/**
 * MarketingIntelligenceSkill — backward-compatible router (Layer 5 — Platform Intelligence)
 *
 * Kept for compatibility with existing DB records and API consumers.
 * All logic has been extracted into focused skill classes:
 *   - CampaignIntelligenceSkill (key: campaign-intelligence) — analyze_campaign, content_brief
 *   - AudienceIntelligenceSkill (key: audience-intelligence) — segment_audience, measure_roi
 *
 * New deployments should reference the focused skills directly.
 */
class MarketingIntelligenceSkill extends MarketingHelper
{
    private CampaignIntelligenceSkill $campaign;

    private AudienceIntelligenceSkill $audience;

    public function __construct()
    {
        $this->campaign = new CampaignIntelligenceSkill;
        $this->audience = new AudienceIntelligenceSkill;
    }

    public function key(): string
    {
        return 'marketing-intelligence';
    }

    public function layer(): string
    {
        return 'platform';
    }

    /**
     * Delegates to CampaignIntelligenceSkill or AudienceIntelligenceSkill based on action.
     *
     * Actions:
     *   analyze_campaign, content_brief → CampaignIntelligenceSkill
     *   segment_audience, measure_roi   → AudienceIntelligenceSkill
     */
    public function execute(array $input, array $context = []): SkillResult
    {
        $action = $input['action'] ?? 'analyze_campaign';

        return match ($action) {
            'analyze_campaign', 'content_brief' => $this->campaign->execute($input, $context),
            default => $this->audience->execute($input, $context),
        };
    }
}
