<?php

namespace App\Events;

use App\Models\AgentDeployment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AgentDeployed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly AgentDeployment $deployment
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("organizations.{$this->deployment->organization_id}"),
        ];
    }
}
