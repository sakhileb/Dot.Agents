<?php

namespace App\Skills\Platform;

use App\Skills\BaseSkill;

/**
 * MarketingHelper — shared marketing content and audience helpers.
 *
 * Extracted from MarketingIntelligenceSkill to eliminate duplication between
 * CampaignIntelligenceSkill and AudienceIntelligenceSkill. Contains pure
 * channel content structure, CTA derivation, success metrics, word counts,
 * and industry messaging angle utilities.
 */
abstract class MarketingHelper extends BaseSkill
{
    /** Build a channel-appropriate content structure outline. */
    protected function buildContentStructure(string $channel): array
    {
        return match ($channel) {
            'blog' => ['Hook / opening paragraph', 'Problem statement', 'Solution overview', '3-5 key points with evidence', 'Case study or example', 'CTA with value proposition'],
            'email' => ['Subject line (A/B variants)', 'Preheader text', 'Personalised greeting', 'Core value proposition (2-3 sentences)', 'Bullet benefits', 'Single strong CTA button'],
            'social' => ['Attention-grabbing first line', 'Core message (1-2 sentences)', 'Supporting visual description', 'Hashtag strategy', 'CTA or question to drive engagement'],
            'video' => ['Hook (0–5 sec)', 'Problem agitation (5–15 sec)', 'Solution reveal (15–30 sec)', 'Proof/social validation (30–45 sec)', 'CTA (final 10 sec)'],
            default => ['Introduction', 'Main content', 'Conclusion', 'CTA'],
        };
    }

    /** Derive a strong CTA from a marketing goal string. */
    protected function deriveCta(string $goal): string
    {
        $lower = strtolower($goal);
        if (str_contains($lower, 'lead') || str_contains($lower, 'signup')) {
            return 'Start your free trial today';
        }
        if (str_contains($lower, 'sale') || str_contains($lower, 'revenue')) {
            return 'Get started — no credit card required';
        }
        if (str_contains($lower, 'brand') || str_contains($lower, 'awareness')) {
            return 'Learn how 500+ companies transformed their operations';
        }

        return 'Request a personalised demo';
    }

    /** Return the primary success metrics to track for a given channel. */
    protected function successMetrics(string $channel): array
    {
        return match ($channel) {
            'blog' => ['organic_traffic', 'time_on_page', 'lead_conversions', 'backlinks'],
            'email' => ['open_rate', 'click_through_rate', 'conversion_rate', 'unsubscribe_rate'],
            'social' => ['reach', 'engagement_rate', 'shares', 'profile_visits', 'link_clicks'],
            'video' => ['view_count', 'watch_time', 'click_through_rate', 'conversion_rate'],
            default => ['reach', 'engagement', 'conversions'],
        };
    }

    /** Return a human-readable target word/character count for a channel. */
    protected function wordCountForChannel(string $channel): string
    {
        return match ($channel) {
            'blog' => '1,000–2,500 words',
            'email' => '150–300 words',
            'social' => '50–280 characters',
            'video' => '150–300 word script (~60–90 sec)',
            default => '500–1,000 words',
        };
    }

    /** Return an industry-specific messaging angle. */
    protected function messagingAngle(string $industry): string
    {
        return match (strtolower($industry)) {
            'finance', 'banking' => 'Compliance-first efficiency and risk reduction',
            'healthcare', 'health' => 'Patient outcomes and regulatory confidence',
            'technology', 'tech', 'software' => 'Velocity, scalability, and developer experience',
            'retail', 'ecommerce' => 'Conversion uplift and seamless customer journeys',
            'manufacturing' => 'Operational efficiency and supply-chain resilience',
            'education' => 'Learning outcomes and accessibility at scale',
            default => 'Operational excellence and measurable ROI',
        };
    }
}
