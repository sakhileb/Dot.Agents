<?php

namespace App\Jobs;

use App\DTOs\Social\SentimentAnalysisData;
use App\Events\NegativeSentimentDetected;
use App\Models\SocialMessage;
use App\Services\Social\SentimentAnalysisService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

class AnalyzeSentimentJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable;

    public int $tries = 2;

    public int $backoff = 30;

    public int $timeout = 60;

    public function __construct(
        public readonly SocialMessage $message
    ) {
        $this->onQueue('social-commerce');
    }

    public function handle(SentimentAnalysisService $sentimentService): void
    {
        try {
            $data = new SentimentAnalysisData(
                organizationId: $this->message->organization_id,
                subjectType: 'conversation',
                text: $this->message->content,
                platform: $this->message->socialConversation->platform ?? '',
                socialConversationId: $this->message->social_conversation_id,
                agentDeploymentId: $this->message->agent_deployment_id,
            );

            $score = $sentimentService->analyze($data);

            if ($score->isNegative()) {
                event(new NegativeSentimentDetected($score));
            }
        } catch (Throwable $e) {
            Log::error('AnalyzeSentimentJob failed', [
                'message_id' => $this->message->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::critical('AnalyzeSentimentJob: all retries exhausted — moved to failed_jobs', [
            'message_id' => $this->message->id,
            'organization_id' => $this->message->organization_id,
            'error' => $exception->getMessage(),
        ]);
    }
}
