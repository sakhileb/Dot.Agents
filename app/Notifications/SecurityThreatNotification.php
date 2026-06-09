<?php

namespace App\Notifications;

use App\Mail\SecurityAlertEmail;
use App\Models\SecurityEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class SecurityThreatNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly SecurityEvent $event,
    ) {
        // High-severity security alerts go on the security queue
        $this->onQueue('security');
    }

    public function via(object $notifiable): array
    {
        $channels = ['database', 'broadcast'];

        // Only email for medium+ severity to avoid alert fatigue on low-severity events
        if (in_array($this->event->severity ?? 'low', ['medium', 'high', 'critical'])) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): SecurityAlertEmail
    {
        return new SecurityAlertEmail($this->event);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'security_threat',
            'event_id' => $this->event->id,
            'event_type' => $this->event->event_type,
            'severity' => $this->event->severity ?? 'medium',
            'description' => $this->event->description,
            'detected_at' => $this->event->created_at?->toISOString(),
            'url' => url('/security/events/'.$this->event->id),
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
