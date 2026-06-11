<?php

namespace App\Jobs;

use App\Models\SocialAccount;
use App\Services\Social\ReputationMonitoringService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

class MonitorBrandMentionsJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable;

    public int $tries = 2;

    public int $backoff = 120;

    public int $timeout = 300;

    public function __construct(
        public readonly int $organizationId,
        public readonly ?int $socialAccountId = null,
    ) {
        $this->onQueue('social-commerce');
    }

    public function handle(ReputationMonitoringService $monitor): void
    {
        try {
            $accounts = $this->socialAccountId
                ? SocialAccount::withoutGlobalScope('organization')
                    ->where('id', $this->socialAccountId)
                    ->where('organization_id', $this->organizationId)
                    ->where('status', 'active')
                    ->get()
                : SocialAccount::withoutGlobalScope('organization')
                    ->where('organization_id', $this->organizationId)
                    ->where('status', 'active')
                    ->get();

            foreach ($accounts as $account) {
                $monitor->scanMentions($account);
            }
        } catch (Throwable $e) {
            Log::error('MonitorBrandMentionsJob failed', [
                'organization_id' => $this->organizationId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
