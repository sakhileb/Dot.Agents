<?php

namespace App\Actions\Security;

use App\DTOs\Security\ResolveSecurityEventData;
use App\Events\SecurityEventResolved;
use App\Models\SecurityEvent;
use App\Services\Governance\AuditService;
use Illuminate\Support\Facades\Gate;

class ResolveSecurityEventAction
{
    public function __construct(private readonly AuditService $auditService) {}

    public function execute(ResolveSecurityEventData $data): SecurityEvent
    {
        $event = SecurityEvent::withoutGlobalScope('organization')
            ->findOrFail($data->eventId);

        Gate::authorize('update', $event);

        $event->update([
            'status' => 'resolved',
            'remediation_notes' => $data->remediationNotes,
        ]);

        $this->auditService->logUserAction(
            event: 'security_event.resolved',
            description: "Security event #{$event->id} ({$event->event_type}) resolved",
            subject: $event,
        );

        event(new SecurityEventResolved($event));

        return $event;
    }
}
