<?php

namespace App\Actions\Security;

use App\Models\SecurityEvent;
use App\Services\Governance\AuditService;
use Illuminate\Support\Facades\Gate;

class ResolveSecurityEventAction
{
    public function __construct(private readonly AuditService $auditService) {}

    public function execute(int $eventId, ?string $notes = null): SecurityEvent
    {
        $event = SecurityEvent::withoutGlobalScope('organization')
            ->findOrFail($eventId);

        Gate::authorize('update', $event);

        $event->update([
            'status' => 'resolved',
            'remediation_notes' => $notes,
        ]);

        $this->auditService->logUserAction(
            event: 'security_event.resolved',
            description: "Security event #{$event->id} ({$event->event_type}) resolved",
            subject: $event,
        );

        return $event;
    }
}
