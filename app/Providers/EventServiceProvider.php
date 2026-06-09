<?php

namespace App\Providers;

use App\Events\AgentDeployed;
use App\Events\AgentDriftDetected;
use App\Events\AgentTaskCompleted;
use App\Events\AgentTaskFailed;
use App\Events\ApprovalProcessed;
use App\Events\ApprovalRequested;
use App\Events\OrganizationCreated;
use App\Events\SecurityThreatDetected;
use App\Listeners\HandleAgentTaskFailed;
use App\Listeners\LogDeploymentAudit;
use App\Listeners\LogSecurityThreat;
use App\Listeners\NotifyOnAgentDrift;
use App\Listeners\NotifyOnApprovalProcessed;
use App\Listeners\SendApprovalNotification;
use App\Listeners\SetupOrganizationDefaults;
use App\Listeners\UpdateScorecardOnTaskComplete;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

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
        AgentDeployed::class => [
            LogDeploymentAudit::class,
        ],

        AgentTaskCompleted::class => [
            UpdateScorecardOnTaskComplete::class,
        ],

        AgentTaskFailed::class => [
            HandleAgentTaskFailed::class,
        ],

        SecurityThreatDetected::class => [
            LogSecurityThreat::class,
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
