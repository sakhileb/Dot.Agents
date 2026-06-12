<?php

namespace App\Listeners;

use App\Events\AgentCapabilityContractChanged;
use App\Models\PlatformNotification;
use App\Services\Governance\AuditService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * TriggerCapabilityContractGovernanceReview
 *
 * Handles the AgentCapabilityContractChanged event.  Creates a governance
 * review record and notifies affected deployment owners so they can assess
 * the impact of the breaking capability change before the new version
 * propagates to autonomous deployments.
 */
class TriggerCapabilityContractGovernanceReview implements ShouldQueue
{
    public string $queue = 'governance';

    public int $tries = 3;

    public function __construct(private readonly AuditService $auditService) {}

    public function handle(AgentCapabilityContractChanged $event): void
    {
        $newVersion = $event->newVersion;
        $prevVersion = $event->previousVersion;
        $agent = $newVersion->agent;

        // Log the breaking change as a governance audit entry
        $this->auditService->logUserAction(
            event: 'agent_capability_contract_changed',
            description: "Agent capability contract changed for agent #{$agent->id} — new version {$newVersion->version} introduces breaking changes",
            subject: $agent,
            metadata: [
                'agent_id' => $agent->id,
                'new_version' => $newVersion->version,
                'previous_version' => $prevVersion->version,
                'new_capabilities' => array_keys($newVersion->capabilities_snapshot ?? []),
                'previous_capabilities' => array_keys($prevVersion->capabilities_snapshot ?? []),
                'breaking_change' => true,
            ]
        );

        // Create a platform notification for the agent owner's organization
        PlatformNotification::create([
            'organization_id' => $agent->organization_id ?? null,
            'type' => 'agent_capability_contract_changed',
            'title' => "Breaking capability change — {$agent->name} v{$newVersion->version}",
            'body' => "Agent \"{$agent->name}\" was published with a breaking capability "
                ."change from v{$prevVersion->version} to v{$newVersion->version}. "
                .'Review active deployments to ensure compatibility.',
            'metadata' => [
                'agent_id' => $agent->id,
                'new_version_id' => $newVersion->id,
                'prev_version_id' => $prevVersion->id,
                'governance_action' => 'review_required',
            ],
            'read_at' => null,
        ]);

        Log::info('[TriggerCapabilityContractGovernanceReview] Breaking change governance review created', [
            'agent_id' => $agent->id,
            'new_version' => $newVersion->version,
            'prev_version' => $prevVersion->version,
        ]);
    }

    public function failed(AgentCapabilityContractChanged $event, Throwable $exception): void
    {
        Log::error('[TriggerCapabilityContractGovernanceReview] Failed to process breaking change event', [
            'agent_id' => $event->newVersion->agent_id,
            'new_version' => $event->newVersion->version,
            'error' => $exception->getMessage(),
        ]);
    }
}
