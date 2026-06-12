<?php

namespace App\Listeners;

use App\Events\OrganizationSettingsUpdated;
use App\Services\Governance\AuditService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

class LogOrganizationSettingsUpdated implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'default';

    public int $tries = 3;

    public function __construct(
        private readonly AuditService $auditService,
    ) {}

    public function handle(OrganizationSettingsUpdated $event): void
    {
        $org = $event->organization;

        $this->auditService->logUserAction(
            event: 'organization.settings_updated',
            description: "Settings updated for organization '{$org->name}'",
            subject: $org,
            metadata: ['changes' => $event->changes],
        );
    }

    public function failed(OrganizationSettingsUpdated $event, Throwable $exception): void
    {
        Log::warning('[LogOrganizationSettingsUpdated] Failed to log settings update', [
            'organization_id' => $event->organization->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
