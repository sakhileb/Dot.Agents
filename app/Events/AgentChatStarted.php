<?php

namespace App\Events;

use App\Models\AgentSession;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AgentChatStarted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly AgentSession $session
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("organizations.{$this->session->organization_id}"),
        ];
    }
}
