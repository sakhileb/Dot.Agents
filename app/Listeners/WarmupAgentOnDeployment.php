<?php

namespace App\Listeners;

use App\Events\AgentDeployed;
use App\Jobs\GenerateAgentScorecard;
use App\Services\AI\AgentReputationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Warms up the agent reputation cache and triggers a baseline scorecard
 * immediately after deployment so the dashboard shows real data from day one
 * rather than empty state.
 */
class WarmupAgentOnDeployment implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'agents';

    public int $tries = 2;

    public function __construct(
        private readonly AgentReputationService $reputationService,
    ) {}

    public function handle(AgentDeployed $event): void
    {
        $deployment = $event->deployment;

        // Pre-compute reputation profile so the first dashboard load is instant
        $this->reputationService->compute(
            deploymentId: $deployment->id,
            organizationId: $deployment->organization_id,
        );

        // Generate a baseline (weekly) scorecard with empty-state defaults
        GenerateAgentScorecard::dispatch($deployment, 'weekly')
            ->delay(now()->addSeconds(10))
            ->onQueue('governance');
    }

    public function failed(AgentDeployed $event, Throwable $exception): void
    {
        Log::warning('[WarmupAgentOnDeployment] Failed to warm up agent', [
            'deployment_id' => $event->deployment->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
