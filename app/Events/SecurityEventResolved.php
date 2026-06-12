<?php

namespace App\Events;

use App\Models\SecurityEvent;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SecurityEventResolved
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly SecurityEvent $securityEvent
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("organizations.{$this->securityEvent->organization_id}"),
        ];
    }
}
