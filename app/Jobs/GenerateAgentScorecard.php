<?php

namespace App\Jobs;

use App\Models\AgentDeployment;
use App\Services\Governance\ScorecardService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateAgentScorecard implements ShouldQueue
{
    use InteractsWithQueue, Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(
        public readonly AgentDeployment $deployment,
        public readonly string $period = 'monthly',
        public readonly ?string $date = null
    ) {
        $this->onQueue('governance');
    }

    public function middleware(): array
    {
        return [new WithoutOverlapping("scorecard-{$this->deployment->id}-{$this->period}")];
    }

    public function handle(ScorecardService $scorecardService): void
    {
        $date = $this->date ? Carbon::parse($this->date) : now();

        $scorecard = $scorecardService->calculatePeriodScorecard(
            $this->deployment,
            $this->period,
            $date
        );

        Log::info('[GenerateAgentScorecard] Scorecard generated', [
            'deployment_id' => $this->deployment->id,
            'scorecard_id' => $scorecard->id,
            'overall_health' => $scorecard->overall_health_score,
            'period' => $this->period,
        ]);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('[GenerateAgentScorecard] Scorecard generation failed', [
            'deployment_id' => $this->deployment->id,
            'period' => $this->period,
            'error' => $exception->getMessage(),
        ]);
    }
}
