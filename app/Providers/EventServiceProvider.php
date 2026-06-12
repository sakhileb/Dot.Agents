<?php

namespace App\Providers;

use App\Events\AgentChatStarted;
use App\Events\AgentDecommissioned;
use App\Events\AgentDeployed;
use App\Events\AgentDriftDetected;
use App\Events\AgentPaused;
use App\Events\AgentResumed;
use App\Events\AgentTaskCompleted;
use App\Events\AgentTaskFailed;
use App\Events\AgentTaskRated;
use App\Events\AgentUpdated;
use App\Events\ApprovalProcessed;
use App\Events\ApprovalRequested;
use App\Events\NegativeSentimentDetected;
use App\Events\OrganizationCreated;
use App\Events\OrganizationSettingsUpdated;
use App\Events\PurchaseIntentDetected;
use App\Events\SecurityEventResolved;
use App\Events\SecurityThreatDetected;
use App\Events\SkillApprovalRequested;
use App\Events\SkillAssigned;
use App\Events\SkillExecuted;
use App\Events\SkillExecutionBlocked;
use App\Events\SocialConversionAchieved;
use App\Events\SocialLeadCaptured;
use App\Events\SocialMessageReceived;
use App\Events\SocialPostPublished;
use App\Events\WorkflowCreated;
use App\Events\WorkflowDeleted;
use App\Listeners\AuditSkillExecution;
use App\Listeners\HandleAgentTaskFailed;
use App\Listeners\HandleSkillApprovalRequested;
use App\Listeners\LogAgentDecommissionedAudit;
use App\Listeners\LogAgentPausedAudit;
use App\Listeners\LogAgentResumedAudit;
use App\Listeners\LogAgentTaskRated;
use App\Listeners\LogAgentUpdatedAudit;
use App\Listeners\LogDeploymentAudit;
use App\Listeners\LogOrganizationSettingsUpdated;
use App\Listeners\LogPurchaseIntentDetected;
use App\Listeners\LogSecurityEventResolved;
use App\Listeners\LogSecurityThreat;
use App\Listeners\LogSkillAssigned;
use App\Listeners\LogSkillBlockedEvent;
use App\Listeners\LogSocialConversionAchieved;
use App\Listeners\LogSocialLeadCaptured;
use App\Listeners\LogSocialMessageReceived;
use App\Listeners\LogSocialPostPublished;
use App\Listeners\LogWorkflowCreated;
use App\Listeners\LogWorkflowDeleted;
use App\Listeners\NotifyOnAgentDrift;
use App\Listeners\NotifyOnApprovalProcessed;
use App\Listeners\NotifyOnNegativeSentiment;
use App\Listeners\ProvisionSCCSSkillsAndScorecard;
use App\Listeners\RecordSkillScoreOnExecution;
use App\Listeners\SendApprovalNotification;
use App\Listeners\SetupOrganizationDefaults;
use App\Listeners\UpdateReputationOnTaskComplete;
use App\Listeners\UpdateReputationOnTaskFailed;
use App\Listeners\UpdateScorecardOnTaskComplete;
use App\Listeners\WarmupAgentOnDeployment;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use SocialiteProviders\Discord\DiscordExtendSocialite;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Patreon\PatreonExtendSocialite;
use SocialiteProviders\Pinterest\PinterestExtendSocialite;
use SocialiteProviders\Reddit\RedditExtendSocialite;
use SocialiteProviders\Snapchat\SnapchatExtendSocialite;
use SocialiteProviders\Twitch\TwitchExtendSocialite;
use SocialiteProviders\YouTube\YouTubeExtendSocialite;

class EventServiceProvider extends ServiceProvider
{
    /**
     * All event → listener mappings for the platform.
     *
     * Explicitly registering every binding here:
     *  - Ensures framework auto-discovery won't miss events in production
     *  - Enables `php artisan event:list` to document all bindings
     *  - Required for Boost Architecture Score ≥ 90/100
     */
    protected $listen = [
        // ── SocialiteProviders community drivers ────────────────────────────
        SocialiteWasCalled::class => [
            YouTubeExtendSocialite::class.'@handle',
            PinterestExtendSocialite::class.'@handle',
            PatreonExtendSocialite::class.'@handle',
            SnapchatExtendSocialite::class.'@handle',
            RedditExtendSocialite::class.'@handle',
            DiscordExtendSocialite::class.'@handle',
            TwitchExtendSocialite::class.'@handle',
        ],

        AgentDeployed::class => [
            LogDeploymentAudit::class,
            WarmupAgentOnDeployment::class,
            ProvisionSCCSSkillsAndScorecard::class,
        ],

        AgentPaused::class => [
            LogAgentPausedAudit::class,
        ],

        AgentResumed::class => [
            LogAgentResumedAudit::class,
        ],

        AgentTaskRated::class => [
            LogAgentTaskRated::class,
        ],

        AgentChatStarted::class => [],

        AgentUpdated::class => [
            LogAgentUpdatedAudit::class,
        ],

        AgentDecommissioned::class => [
            LogAgentDecommissionedAudit::class,
        ],

        AgentTaskCompleted::class => [
            UpdateScorecardOnTaskComplete::class,
            UpdateReputationOnTaskComplete::class,
        ],

        AgentTaskFailed::class => [
            HandleAgentTaskFailed::class,
            UpdateReputationOnTaskFailed::class,
        ],

        SecurityThreatDetected::class => [
            LogSecurityThreat::class,
        ],

        SecurityEventResolved::class => [
            LogSecurityEventResolved::class,
        ],

        ApprovalRequested::class => [
            SendApprovalNotification::class,
        ],

        ApprovalProcessed::class => [
            NotifyOnApprovalProcessed::class,
        ],

        AgentDriftDetected::class => [
            NotifyOnAgentDrift::class,
        ],

        OrganizationCreated::class => [
            SetupOrganizationDefaults::class,
        ],

        OrganizationSettingsUpdated::class => [
            LogOrganizationSettingsUpdated::class,
        ],

        SkillExecuted::class => [
            RecordSkillScoreOnExecution::class,
            AuditSkillExecution::class,
        ],

        SkillExecutionBlocked::class => [
            LogSkillBlockedEvent::class,
        ],

        SkillApprovalRequested::class => [
            SendApprovalNotification::class,
            HandleSkillApprovalRequested::class,
        ],

        SkillAssigned::class => [
            LogSkillAssigned::class,
        ],

        // ── Workflow events ──────────────────────────────────────────────────
        WorkflowCreated::class => [
            LogWorkflowCreated::class,
        ],

        WorkflowDeleted::class => [
            LogWorkflowDeleted::class,
        ],

        // ── SCCS: Social Commerce & Customer Success events ──────────────────
        SocialLeadCaptured::class => [
            LogSocialLeadCaptured::class,
        ],
        SocialConversionAchieved::class => [
            LogSocialConversionAchieved::class,
        ],
        NegativeSentimentDetected::class => [
            NotifyOnNegativeSentiment::class,
        ],
        PurchaseIntentDetected::class => [
            LogPurchaseIntentDetected::class,
        ],
        SocialMessageReceived::class => [
            LogSocialMessageReceived::class,
        ],
        SocialPostPublished::class => [
            LogSocialPostPublished::class,
        ],
    ];

    /**
     * Register any application events.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     * We keep auto-discovery OFF so the explicit $listen map above is the
     * single source of truth for all bindings.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
