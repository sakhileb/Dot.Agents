<?php

namespace App\Events;

use App\Models\AgentDeployment;
use App\Models\AgentSkill;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SkillExecutionBlocked
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly AgentSkill $skill,
        public readonly AgentDeployment $deployment,
        public readonly string $reason,
        public readonly int $organizationId,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("organizations.{$this->organizationId}"),
        ];
    }
}
