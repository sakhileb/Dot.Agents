<?php

namespace App\Events;

use App\Models\AgentDeployment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AgentDriftDetected
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly AgentDeployment $deployment,
        public readonly string $driftType,
        public readonly string $severity,
        public readonly array $details = []
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("organizations.{$this->deployment->organization_id}"),
        ];
    }
}
