<?php

namespace App\Jobs;

use App\Models\AgentMemory;
use App\Services\AI\VectorMemoryService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Asynchronously generates and stores the vector embedding for a memory.
 * Runs on the 'agent-tasks' queue to avoid blocking the request cycle.
 */
class EmbedAgentMemoryJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        private readonly AgentMemory $memory,
    ) {}

    public function handle(VectorMemoryService $vectorService): void
    {
        $vectorService->embedAndStoreMemory($this->memory);
    }
}
