<?php

namespace App\Jobs;

use App\Actions\Social\CaptureLeadAction;
use App\DTOs\Social\CaptureLeadData;
use App\Models\SocialMessage;
use App\Services\Social\ConversationContinuationService;
use App\Services\Social\LeadQualificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateSocialResponseJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable;

    public int $tries = 3;

    public int $backoff = 15;

    public int $timeout = 120;

    public function __construct(
        public readonly SocialMessage $message
    ) {
        $this->onQueue('social-commerce');
    }

    public function middleware(): array
    {
        return [
            new WithoutOverlapping("social-conv-{$this->message->social_conversation_id}"),
        ];
    }

    public function handle(
        ConversationContinuationService $conversationService,
        LeadQualificationService $leadService,
        CaptureLeadAction $captureLeadAction,
        ?AnalyzeSentimentJob $analyzeSentimentJob = null,
    ): void {
        $conversation = $this->message->socialConversation;

        if (! $conversation || ! $conversation->agent_deployment_id) {
            return;
        }

        try {
            $startTime = microtime(true);

            // 1. Analyze intent and sentiment in parallel
            AnalyzeSentimentJob::dispatch($this->message);

            // 2. Score purchase intent
            $intentResult = $leadService->scoreIntent($conversation, $this->message->content);

            // 3. Capture or update lead if intent is meaningful
            if ($intentResult['intent_score'] >= 25) {
                $captureLeadAction->execute(CaptureLeadData::fromArray([
                    'organization_id' => $conversation->organization_id,
                    'platform' => $conversation->platform,
                    'contact_platform_id' => $conversation->contact_platform_id,
                    'social_conversation_id' => $conversation->id,
                    'agent_deployment_id' => $conversation->agent_deployment_id,
                    'contact_name' => $conversation->contact_name,
                    'contact_handle' => $conversation->contact_handle,
                    'intent_level' => $intentResult['intent_level'],
                    'intent_score' => $intentResult['intent_score'],
                    'lead_score' => $intentResult['lead_score'],
                    'recommended_actions' => $intentResult['recommended_actions'],
                ]));
            }

            // 4. Generate AI response using conversation continuation engine
            $responseMessage = $conversationService->generateResponse(
                conversation: $conversation,
                inboundMessage: $this->message,
                intentResult: $intentResult,
            );

            $latencyMs = (int) ((microtime(true) - $startTime) * 1000);

            Log::info('GenerateSocialResponseJob: response generated', [
                'conversation_id' => $conversation->id,
                'requires_approval' => $responseMessage->requires_approval,
                'latency_ms' => $latencyMs,
            ]);
        } catch (Throwable $e) {
            Log::error('GenerateSocialResponseJob failed', [
                'message_id' => $this->message->id,
                'conversation_id' => $this->message->social_conversation_id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
