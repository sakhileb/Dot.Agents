<?php

namespace App\Services\Social;

use App\Models\AgentDeployment;
use App\Models\SocialConversation;
use App\Models\SocialMessage;
use App\Services\Governance\AuditService;
use App\Services\Governance\DelusionDetectionService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use OpenAI\Laravel\Facades\OpenAI;
use Throwable;

/**
 * Conversation Continuation Engine.
 *
 * Transforms transactional responses into engaging, conversion-oriented
 * conversations that increase engagement, retention, and revenue.
 *
 * Enterprise Rule: AI messages that are outbound to customers MUST be
 * disclosed as AI-generated unless the organization has explicit approval
 * for non-disclosed autonomous engagement.
 */
class ConversationContinuationService
{
    public function __construct(
        private readonly AuditService $auditService,
        private readonly DelusionDetectionService $delusionDetector,
    ) {}

    /**
     * Generate an AI response for an inbound message.
     * Respects organizational approval thresholds and governance rules.
     */
    public function generateResponse(
        SocialConversation $conversation,
        SocialMessage $inboundMessage,
        array $intentResult = [],
    ): SocialMessage {
        $deployment = AgentDeployment::withoutGlobalScope('organization')
            ->find($conversation->agent_deployment_id);

        if (! $deployment) {
            throw new \RuntimeException('No agent deployment found for conversation.');
        }

        $requiresApproval = $this->requiresHumanApproval($deployment, $intentResult);
        $shouldDiscloseAi = $this->shouldDiscloseAsAi($deployment);

        try {
            $responseContent = $this->generateAIContent(
                conversation: $conversation,
                inboundMessage: $inboundMessage,
                deployment: $deployment,
                intentResult: $intentResult,
                shouldDiscloseAi: $shouldDiscloseAi,
            );
        } catch (Throwable $e) {
            Log::error('ConversationContinuationService: AI generation failed', [
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        // ── Delusion detection on AI-generated response ───────────────────────
        $delusionAnalysis = $this->delusionDetector->analyze(
            $inboundMessage->content,
            ['content' => $responseContent['content'], 'confidence' => $responseContent['confidence']],
            ['conversation_id' => $conversation->id, 'intent' => $intentResult]
        );

        // Force human approval if delusion risk is elevated
        if ($delusionAnalysis['risk_score'] >= 60) {
            $requiresApproval = true;
            Log::warning('ConversationContinuationService: elevated delusion risk forces approval', [
                'conversation_id' => $conversation->id,
                'delusion_risk_score' => $delusionAnalysis['risk_score'],
                'flags' => $delusionAnalysis['flags'],
            ]);
        }

        $message = SocialMessage::create([
            'uuid' => (string) Str::uuid(),
            'organization_id' => $conversation->organization_id,
            'social_conversation_id' => $conversation->id,
            'agent_deployment_id' => $deployment->id,
            'direction' => 'outbound',
            'sender_type' => 'agent',
            'sender_id' => (string) $deployment->id,
            'sender_name' => $deployment->name,
            'content' => $responseContent['content'],
            'message_type' => 'text',
            'status' => $requiresApproval ? 'pending' : 'sent',
            'is_ai_generated' => true,
            'requires_approval' => $requiresApproval,
            'approval_status' => $requiresApproval ? 'pending' : null,
            'was_disclosed_as_ai' => $shouldDiscloseAi,
            'ai_confidence' => $responseContent['confidence'],
            'ai_context' => [
                'intent_level' => $intentResult['intent_level'] ?? null,
                'intent_score' => $intentResult['intent_score'] ?? null,
                'strategy' => $responseContent['strategy'] ?? null,
                'recommended_actions' => $intentResult['recommended_actions'] ?? [],
                'delusion_risk_score' => $delusionAnalysis['risk_score'],
                'delusion_flags' => $delusionAnalysis['flags'],
            ],
            'sent_at' => $requiresApproval ? null : now(),
        ]);

        $this->auditService->logUserAction(
            event: 'social_message.ai_generated',
            description: 'AI-generated social message created',
            data: [
                'requires_approval' => $requiresApproval,
                'was_disclosed_as_ai' => $shouldDiscloseAi,
                'confidence' => $responseContent['confidence'],
                'intent_level' => $intentResult['intent_level'] ?? null,
            ],
            subject: $message,
        );

        return $message;
    }

    private function generateAIContent(
        SocialConversation $conversation,
        SocialMessage $inboundMessage,
        AgentDeployment $deployment,
        array $intentResult,
        bool $shouldDiscloseAi,
    ): array {
        $recentMessages = $conversation->messages()
            ->latest()
            ->limit(10)
            ->get()
            ->reverse()
            ->map(fn (SocialMessage $m) => [
                'role' => $m->direction === 'inbound' ? 'user' : 'assistant',
                'content' => $m->content,
            ])
            ->values()
            ->toArray();

        $intentContext = '';
        if (! empty($intentResult)) {
            $intentContext = "\n\nIntent context: Customer shows {$intentResult['intent_level']} intent (score: {$intentResult['intent_score']}).";
            if (! empty($intentResult['recommended_actions'])) {
                $actions = implode(', ', $intentResult['recommended_actions']);
                $intentContext .= " Recommended actions: {$actions}.";
            }
        }

        $disclosureInstruction = $shouldDiscloseAi
            ? 'If appropriate, naturally disclose you are an AI assistant (e.g., "As your AI assistant..." or "I\'m an AI here to help...").'
            : '';

        $systemPrompt = <<<SYSTEM
You are {$deployment->name}, a Customer Success Agent for an enterprise organization.
Your goal is to engage naturally, answer questions helpfully, and guide the customer toward a positive outcome.

Conversation continuation principles:
1. Never give one-word or transactional responses — always continue the conversation naturally.
2. Ask a relevant follow-up question to increase engagement.
3. If intent is high, subtly guide toward next steps (demo, quote, purchase).
4. Be empathetic, professional, and concise.
5. Never promise refunds, discounts beyond authority, or make legal commitments.
6. Never share confidential organizational data.
7. If the customer is frustrated or angry, acknowledge their frustration first before resolving.
{$disclosureInstruction}
{$deployment->custom_instructions}
{$intentContext}

Respond with JSON: {"content": "your message here", "strategy": "engagement_strategy_used", "confidence": 0-100}
SYSTEM;

        $messages = array_merge(
            [['role' => 'system', 'content' => $systemPrompt]],
            $recentMessages,
        );

        $response = OpenAI::chat()->create([
            'model' => $deployment->model_override ?? 'gpt-4o-mini',
            'temperature' => 0.7,
            'max_tokens' => 500,
            'response_format' => ['type' => 'json_object'],
            'messages' => $messages,
        ]);

        $raw = json_decode($response->choices[0]->message->content, true);

        return [
            'content' => $raw['content'] ?? 'Thank you for your message. How can I assist you further?',
            'strategy' => $raw['strategy'] ?? 'general_engagement',
            'confidence' => (float) ($raw['confidence'] ?? 80),
        ];
    }

    private function requiresHumanApproval(AgentDeployment $deployment, array $intentResult): bool
    {
        if ($deployment->requires_human_approval) {
            return true;
        }

        // High-risk intent actions (discounts, demos, transfers) always require approval
        // unless deployment mode is fully autonomous
        if ($deployment->deployment_mode === 'autonomous') {
            return false;
        }

        $highRiskActions = ['offer_discount', 'transfer_to_sales', 'book_demo', 'generate_quote'];
        $recommendedActions = $intentResult['recommended_actions'] ?? [];

        return ! empty(array_intersect($recommendedActions, $highRiskActions));
    }

    private function shouldDiscloseAsAi(AgentDeployment $deployment): bool
    {
        // Enterprise rule: AI must never fully impersonate a human without disclosure
        // Disclosure can only be suppressed if organization explicitly enables non-disclosed mode
        $settings = $deployment->context_config ?? [];

        return ! ($settings['suppress_ai_disclosure'] ?? false);
    }
}
