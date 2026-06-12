<?php

namespace App\Livewire\Concerns;

/**
 * ManagesOAuthFlow
 *
 * Provides all static platform catalog data and step-navigation methods
 * for the ConnectPlatformWizard component.
 *
 * The main component retains only lifecycle hooks, computed properties,
 * the activate() method, and render() — keeping it well under 200 lines.
 */
trait ManagesOAuthFlow
{
    public static function platforms(): array
    {
        return [
            'facebook' => ['label' => 'Facebook Business', 'short' => 'Facebook',  'icon' => 'f',  'color' => 'bg-blue-600',   'ring' => 'ring-blue-400'],
            'instagram' => ['label' => 'Instagram Business', 'short' => 'Instagram', 'icon' => 'ig', 'color' => 'bg-pink-600',   'ring' => 'ring-pink-400'],
            'linkedin' => ['label' => 'LinkedIn',           'short' => 'LinkedIn',  'icon' => 'in', 'color' => 'bg-sky-700',    'ring' => 'ring-sky-400'],
            'twitter' => ['label' => 'X (Twitter)',        'short' => 'X',         'icon' => 'X',  'color' => 'bg-gray-900',   'ring' => 'ring-gray-500'],
            'tiktok' => ['label' => 'TikTok',             'short' => 'TikTok',    'icon' => 'tt', 'color' => 'bg-black',      'ring' => 'ring-gray-400'],
            'youtube' => ['label' => 'YouTube',            'short' => 'YouTube',   'icon' => 'yt', 'color' => 'bg-red-600',    'ring' => 'ring-red-400'],
            'pinterest' => ['label' => 'Pinterest',          'short' => 'Pinterest', 'icon' => 'P',  'color' => 'bg-red-700',    'ring' => 'ring-red-500'],
            'patreon' => ['label' => 'Patreon',            'short' => 'Patreon',   'icon' => 'pa', 'color' => 'bg-orange-600', 'ring' => 'ring-orange-400'],
            'snapchat' => ['label' => 'Snapchat',           'short' => 'Snapchat',  'icon' => 'sc', 'color' => 'bg-yellow-400', 'ring' => 'ring-yellow-300'],
            'reddit' => ['label' => 'Reddit',             'short' => 'Reddit',    'icon' => 'r',  'color' => 'bg-orange-500', 'ring' => 'ring-orange-400'],
            'discord' => ['label' => 'Discord',            'short' => 'Discord',   'icon' => 'dc', 'color' => 'bg-indigo-600', 'ring' => 'ring-indigo-400'],
            'twitch' => ['label' => 'Twitch',             'short' => 'Twitch',    'icon' => 'tw', 'color' => 'bg-purple-700', 'ring' => 'ring-purple-400'],
        ];
    }

    public static function goals(): array
    {
        return [
            'generate_leads' => ['label' => 'Generate Leads',      'icon' => '🎯', 'desc' => 'Capture and qualify potential customers from social conversations.'],
            'increase_sales' => ['label' => 'Increase Sales',       'icon' => '💰', 'desc' => 'Convert social interactions into revenue and closed deals.'],
            'provide_support' => ['label' => 'Customer Support',     'icon' => '🛟', 'desc' => 'Resolve customer issues and answer questions automatically, 24/7.'],
            'grow_community' => ['label' => 'Grow Community',       'icon' => '🌱', 'desc' => 'Engage followers and build a loyal brand community.'],
            'improve_reviews' => ['label' => 'Improve Reviews',      'icon' => '⭐', 'desc' => 'Monitor and respond to reviews to protect and grow your reputation.'],
            'build_brand_awareness' => ['label' => 'Brand Awareness',    'icon' => '📣', 'desc' => 'Increase visibility and reach across your social platforms.'],
        ];
    }

    public static function aiFeatures(): array
    {
        return [
            'customer_support' => ['label' => 'Customer Support',        'desc' => 'Automatically handle messages, complaints, and support requests.'],
            'lead_generation' => ['label' => 'Lead Generation',         'desc' => 'Identify and qualify leads from social conversations.'],
            'reputation_monitoring' => ['label' => 'Reputation Monitoring',   'desc' => 'Track sentiment, reviews, and brand mentions in real time.'],
            'social_media_management' => ['label' => 'Social Media Management', 'desc' => 'Schedule, draft, and manage posts and campaigns.'],
            'sales_conversion' => ['label' => 'Sales Conversion',        'desc' => 'Follow up on leads and nurture prospects toward a purchase.'],
        ];
    }

    public static function permissionsList(): array
    {
        return [
            'reply_comments' => ['label' => 'Reply to Comments',      'risk' => 'low',    'icon' => '💬'],
            'reply_messages' => ['label' => 'Reply to Messages',      'risk' => 'low',    'icon' => '✉️'],
            'answer_faqs' => ['label' => 'Answer FAQs',            'risk' => 'low',    'icon' => '❓'],
            'create_support_tickets' => ['label' => 'Create Support Tickets', 'risk' => 'low',    'icon' => '🎫'],
            'offer_discounts' => ['label' => 'Offer Discounts',        'risk' => 'medium', 'icon' => '🏷️'],
            'issue_refunds' => ['label' => 'Issue Refunds',          'risk' => 'high',   'icon' => '💳'],
            'delete_comments' => ['label' => 'Delete Comments',        'risk' => 'high',   'icon' => '🗑️'],
        ];
    }

    public static function autonomyLevels(): array
    {
        return [
            0 => ['label' => 'Observe Only',         'desc' => 'AI monitors activity but takes no action.'],
            1 => ['label' => 'Draft Responses',       'desc' => 'AI writes replies for your team to review and send.'],
            2 => ['label' => 'Auto-Reply (Safe)',      'desc' => 'AI handles routine interactions; complex ones escalate.'],
            3 => ['label' => 'Full Customer Support', 'desc' => 'AI manages all customer interactions end-to-end.'],
            4 => ['label' => 'Sales & Conversion',    'desc' => 'AI handles support, leads, and sales autonomously.'],
        ];
    }

    public static function platformAccess(): array
    {
        return [
            'facebook' => ['Read Messages', 'Send Messages as Your Page', 'Read Comments', 'Reply to Comments', 'Manage Your Pages', 'View Page Insights'],
            'instagram' => ['View Your Instagram Profile', 'Read and Reply to Comments', 'Access Instagram Insights', 'Manage Instagram Posts'],
            'linkedin' => ['Read Your Profile', 'Post on Your Behalf', 'Manage Your Company Pages', 'View Analytics'],
            'twitter' => ['Read Tweets & DMs', 'Send Tweets & DMs', 'View Your Followers', 'View Analytics'],
            'youtube' => ['Read Your Channel Info', 'Read & Reply to Comments', 'View Video Analytics', 'Manage Community Posts'],
            'pinterest' => ['Read Your Boards & Pins', 'Create and Manage Pins', 'Read & Respond to Messages', 'View Analytics'],
            'patreon' => ['Read Your Campaign Info', 'View Your Members & Tiers', 'Send Messages to Patrons'],
            'snapchat' => ['Read Your Profile', 'Manage Your Ad Accounts', 'View Campaign Analytics'],
            'reddit' => ['Read Posts & Comments', 'Submit Posts & Comments', 'Reply to Private Messages', 'Manage Subreddits'],
            'discord' => ['Read Your Profile', 'View Server Members', 'Read & Send Messages in Channels', 'View Server Analytics'],
            'twitch' => ['Read Your Channel Info', 'Manage Broadcasts', 'Read Chat Messages', 'Send Chat Messages', 'View Subscriber List'],
            'tiktok' => ['View Your Profile', 'Read Video List', 'Publish Videos', 'Read and Reply to Comments'],
        ];
    }

    // ── Step navigation ───────────────────────────────────────────────────────

    public function selectPlatform(string $platform): void
    {
        abort_unless(array_key_exists($platform, self::platforms()), 422, 'Invalid platform.');
        $this->selectedPlatform = $platform;
        $this->step = 2;
    }

    public function nextStep(): void
    {
        if ($this->step === 2 && $this->connectionMode === 'advanced') {
            $this->validate([
                'advClientId' => ['required', 'string', 'max:500'],
                'advClientSecret' => ['required', 'string', 'max:500'],
            ], [
                'advClientId.required' => 'Client ID is required for advanced setup.',
                'advClientSecret.required' => 'Client Secret is required for advanced setup.',
            ]);
        }

        if ($this->step === 3 && empty($this->selectedGoals)) {
            $this->addError('selectedGoals', 'Please choose at least one business goal.');

            return;
        }

        $this->step++;
    }

    public function prevStep(): void
    {
        if ($this->step > 1) {
            $this->step--;
        }
    }

    public function jumpToStep(int $step): void
    {
        if ($step < $this->step && $step >= 1) {
            $this->step = $step;
        }
    }
}
