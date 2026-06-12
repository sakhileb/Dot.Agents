<?php

namespace App\Listeners;

use App\Events\SocialPostPublished;
use App\Services\Governance\AuditService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

class LogSocialPostPublished implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'default';

    public int $tries = 3;

    public function __construct(
        private readonly AuditService $auditService,
    ) {}

    public function handle(SocialPostPublished $event): void
    {
        $post = $event->post;

        $this->auditService->logAgentAction(
            $post->deployment,
            'social.post_published',
            [
                'post_id' => $post->id,
                'platform' => $post->platform,
                'scheduled_at' => $post->scheduled_at?->toISOString(),
                'published_at' => $post->published_at?->toISOString(),
            ]
        );
    }

    public function failed(SocialPostPublished $event, Throwable $exception): void
    {
        Log::warning('[LogSocialPostPublished] Failed', [
            'post_id' => $event->post->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
