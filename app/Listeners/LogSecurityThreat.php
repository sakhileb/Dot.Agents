<?php

namespace App\Listeners;

use App\Events\SecurityThreatDetected;
use App\Jobs\SendPlatformNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

class LogSecurityThreat implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'governance';

    public int $tries = 5;

    public int $backoff = 5;

    public function handle(SecurityThreatDetected $event): void
    {
        $secEvent = $event->securityEvent;

        Log::channel('security')->warning('[SecurityThreat] '.$secEvent->title, [
            'event_id' => $secEvent->id,
            'organization_id' => $secEvent->organization_id,
            'event_type' => $secEvent->event_type,
            'severity' => $secEvent->severity,
            'source_ip' => $secEvent->source_ip,
        ]);

        // Escalate critical events to all admins immediately
        if (in_array($secEvent->severity, ['critical', 'error'])) {
            SendPlatformNotification::toAdmins(
                organizationId: $secEvent->organization_id,
                type: 'security_threat',
                title: "🚨 Security Alert: {$secEvent->title}",
                message: $secEvent->description,
                severity: 'error',
                data: [
                    'security_event_id' => $secEvent->id,
                    'event_type' => $secEvent->event_type,
                    'severity' => $secEvent->severity,
                ],
                actionUrl: '/security'
            );
        }

        // Auto-resolve low-severity events that are system-detected
        if ($secEvent->severity === 'info' && $secEvent->auto_remediated) {
            $secEvent->update(['status' => 'resolved']);
        }
    }

    public function failed(SecurityThreatDetected $event, Throwable $exception): void
    {
        // Use fallback channel — never lose a security event log
        Log::critical('[LogSecurityThreat] Failed to process security event', [
            'security_event_id' => $event->securityEvent->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
