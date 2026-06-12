<?php

namespace App\Listeners;

use App\Events\SecurityEventResolved;
use App\Jobs\SendPlatformNotification;
use App\Services\Governance\AuditService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

class LogSecurityEventResolved implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'governance';

    public int $tries = 3;

    public function __construct(
        private readonly AuditService $auditService,
    ) {}

    public function handle(SecurityEventResolved $event): void
    {
        $securityEvent = $event->securityEvent;

        $this->auditService->logUserAction(
            event: 'security_event.resolved',
            description: "Security event #{$securityEvent->id} ({$securityEvent->event_type}) resolved",
            subject: $securityEvent,
            metadata: [
                'event_type' => $securityEvent->event_type,
                'remediation_notes' => $securityEvent->remediation_notes,
            ],
        );

        SendPlatformNotification::toAdmins(
            organizationId: $securityEvent->organization_id,
            type: 'security_event_resolved',
            title: "Security Event Resolved: {$securityEvent->event_type}",
            message: 'A security event has been resolved and closed.',
            severity: 'info',
            data: ['security_event_id' => $securityEvent->id],
            actionUrl: '/security'
        );
    }

    public function failed(SecurityEventResolved $event, Throwable $exception): void
    {
        Log::warning('[LogSecurityEventResolved] Failed to log security resolution audit', [
            'security_event_id' => $event->securityEvent->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
