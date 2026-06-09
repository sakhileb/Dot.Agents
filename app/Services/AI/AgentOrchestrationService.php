<?php

namespace App\Services\AI;

use App\Models\AgentApproval;
use App\Models\AgentDeployment;
use App\Models\AgentMessage;
use App\Models\AgentSession;
use App\Models\AgentTask;
use App\Models\AgentWorkflow;
use App\Models\DecisionLog;
use App\Models\UsageRecord;
use App\Services\Governance\AuditService;
use App\Services\Governance\DelusionDetectionService;
use App\Services\Resilience\CircuitBreakerService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AgentOrchestrationService
{
    public function __construct(
        private readonly ModelRouterService $modelRouter,
        private readonly DelusionDetectionService $delusionDetector,
        private readonly AuditService $auditService,
        private readonly MemoryService $memoryService,
        private readonly AgentSandboxService $sandbox,
        private readonly CircuitBreakerService $circuitBreaker,
        private readonly OutputModerationService $outputModeration,
    ) {}

    /**
     * Process a user message and generate an agent response
     */
    public function processMessage(
        AgentDeployment $deployment,
        AgentSession $session,
        string $userMessage,
        array $context = []
    ): AgentMessage {
        // Enforce sandbox boundaries before processing
        $this->sandbox->assertPermitted($deployment, 'process_message', $context);

        // Build conversation history
        $history = $this->buildConversationHistory($session, $deployment);

        // Inject memory context
        $memoryContext = $this->memoryService->getRelevantMemories($deployment, $userMessage);

        // Build system prompt — cached per deployment for 10 minutes
        $systemPrompt = Cache::remember(
            "agent_system_prompt_{$deployment->id}",
            600,
            fn () => $this->buildSystemPrompt($deployment, $memoryContext, $context)
        );

        // Execute inference with multi-level failover
        $startTime = microtime(true);
        $response = $this->callWithFailover($deployment, $systemPrompt, $history, $userMessage);
        $latencyMs = (int) ((microtime(true) - $startTime) * 1000);

        // Run output moderation — redact PII and block unsafe content
        ['content' => $moderatedContent, 'scan' => $moderationScan] = $this->outputModeration->scanAndRedact(
            $response['content'],
            ['deployment_id' => $deployment->id, 'session_id' => $session->id]
        );

        if ($moderationScan['verdict'] === OutputModerationService::BLOCK) {
            $moderatedContent = $this->outputModeration->blockedResponse();
        }

        // Store assistant message
        $assistantMessage = AgentMessage::create([
            'session_id' => $session->id,
            'role' => 'assistant',
            'content' => $moderatedContent,
            'token_count' => $response['usage']['total_tokens'] ?? 0,
            'cost' => $response['cost'] ?? 0,
            'model_used' => $response['model_used'] ?? 'gpt-4o',
            'latency_ms' => $latencyMs,
            'flagged' => $moderationScan['flagged'],
            'flag_reason' => $moderationScan['flag_reason'],
            'metadata' => [
                'finish_reason' => $response['finish_reason'] ?? null,
                'tool_calls' => $response['tool_calls'] ?? null,
                'moderation_verdict' => $moderationScan['verdict'],
                'provider' => $response['provider'] ?? null,
            ],
        ]);

        // Update session stats
        $session->increment('message_count', 2); // user + assistant
        $session->increment('token_count', $response['usage']['total_tokens'] ?? 0);

        // Record usage
        $this->recordUsage($deployment, $response, $session);

        // Update memory with this interaction
        if ($deployment->enable_memory) {
            $this->memoryService->processInteraction($deployment, $userMessage, $response['content']);
        }

        // Log the audit trail
        $this->auditService->logAgentAction($deployment, 'message_processed', [
            'session_id' => $session->id,
            'message_id' => $assistantMessage->id,
            'token_count' => $response['usage']['total_tokens'] ?? 0,
        ]);

        return $assistantMessage;
    }

    /**
     * Execute an agent task with full governance
     */
    public function executeTask(AgentDeployment $deployment, AgentTask $task): AgentTask
    {
        // Enforce sandbox boundaries before execution
        $this->sandbox->assertPermitted($deployment, 'execute_task', [
            'task_id' => $task->id,
            'organization_id' => $deployment->organization_id,
        ]);

        $task->update(['status' => 'in_progress', 'started_at' => now()]);

        try {
            // Build task prompt
            $taskPrompt = $this->buildTaskPrompt($deployment, $task);

            $persona = $deployment->agent->defaultPersona;
            $systemPrompt = Cache::remember(
                "agent_system_prompt_{$deployment->id}",
                600,
                fn () => $persona?->system_prompt ?? $this->buildDefaultSystemPrompt($deployment)
            );

            $startTime = microtime(true);
            $response = $this->callWithFailover($deployment, $systemPrompt, [], $taskPrompt);
            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            // Parse structured output
            $output = $this->parseTaskOutput($response['content']);

            // Run delusion detection on the output
            $delusionAnalysis = $this->delusionDetector->analyze(
                $task->description,
                $output,
                $task->input_data ?? []
            );

            $confidenceScore = $output['confidence'] ?? 75.0;
            $requiresApproval = $deployment->requiresApprovalFor($confidenceScore)
                || $delusionAnalysis['risk_score'] >= 60;

            $task->update([
                'status' => $requiresApproval ? 'awaiting_approval' : 'completed',
                'output_data' => $output,
                'result_summary' => $output['summary'] ?? substr($response['content'], 0, 500),
                'confidence_score' => $confidenceScore,
                'risk_score' => $delusionAnalysis['risk_score'],
                'delusion_risk_score' => $delusionAnalysis['risk_score'],
                'reality_alignment_score' => $delusionAnalysis['reality_alignment'],
                'actual_duration_minutes' => (int) ($durationMs / 60000),
                'token_count' => $response['usage']['total_tokens'] ?? 0,
                'cost' => $response['cost'] ?? 0,
                'completed_at' => $requiresApproval ? null : now(),
            ]);

            // Create decision log
            $decisionLog = DecisionLog::create([
                'agent_deployment_id' => $deployment->id,
                'organization_id' => $deployment->organization_id,
                'task_id' => $task->id,
                'decision_type' => $task->task_type,
                'title' => "Task: {$task->title}",
                'decision_summary' => $task->result_summary,
                'reasoning' => $output['reasoning'] ?? null,
                'evidence_used' => $output['evidence'] ?? null,
                'confidence_score' => $confidenceScore,
                'risk_score' => $delusionAnalysis['risk_score'],
                'impact_score' => $output['impact_score'] ?? 50,
                'delusion_risk_score' => $delusionAnalysis['risk_score'],
                'reality_alignment_score' => $delusionAnalysis['reality_alignment'],
                'verification_score' => $delusionAnalysis['verification_score'],
                'evidence_quality_score' => $delusionAnalysis['evidence_quality'],
                'source_credibility_score' => $delusionAnalysis['source_credibility'],
                'assumption_count' => $delusionAnalysis['assumption_count'],
                'delusion_analysis' => $delusionAnalysis['analysis'],
                'requires_human_review' => $requiresApproval,
            ]);

            // Create approval request if needed
            if ($requiresApproval) {
                $this->createApprovalRequest($deployment, $task, $decisionLog, $delusionAnalysis);
            }

            // Record usage
            $this->recordUsage($deployment, $response, null, $task);

            // Audit log
            $this->auditService->logAgentAction($deployment, 'task_executed', [
                'task_id' => $task->id,
                'status' => $task->status,
                'requires_approval' => $requiresApproval,
                'confidence_score' => $confidenceScore,
                'delusion_risk' => $delusionAnalysis['risk_score'],
            ]);

        } catch (\Throwable $e) {
            $task->update([
                'status' => 'failed',
                'output_data' => ['error' => $e->getMessage()],
                'completed_at' => now(),
            ]);

            $this->auditService->logAgentAction($deployment, 'task_failed', [
                'task_id' => $task->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }

        return $task->fresh();
    }

    private function buildConversationHistory(AgentSession $session, AgentDeployment $deployment): array
    {
        return $session->messages()
            ->orderBy('created_at')
            ->take(20)
            ->get()
            ->map(fn ($msg) => [
                'role' => $msg->role,
                'content' => $msg->content,
            ])
            ->toArray();
    }

    private function buildSystemPrompt(AgentDeployment $deployment, array $memoryContext, array $context): string
    {
        $agent = $deployment->agent;
        $persona = $agent->defaultPersona;

        $basePrompt = $persona?->system_prompt ?? $this->buildDefaultSystemPrompt($deployment);

        if (! empty($memoryContext)) {
            $memorySection = "\n\n## Relevant Memory Context\n".implode("\n", array_map(
                fn ($m) => "- [{$m['type']}] {$m['content']}",
                $memoryContext
            ));
            $basePrompt .= $memorySection;
        }

        if (! empty($deployment->custom_instructions)) {
            $basePrompt .= "\n\n## Organization-Specific Instructions\n".$deployment->custom_instructions;
        }

        return $basePrompt;
    }

    private function buildDefaultSystemPrompt(AgentDeployment $deployment): string
    {
        $agent = $deployment->agent;
        $org = $deployment->organization;

        return "You are {$agent->name}, an AI agent deployed at {$org->name}. "
            ."Your role: {$agent->description}. "
            ."Deployment mode: {$deployment->deployment_mode}. "
            .'Always be precise, honest about uncertainty, and flag when you need human review. '
            .'Never fabricate data, statistics, or references. '
            .'If confidence is below 75%, explicitly state uncertainty and recommend verification.';
    }

    private function buildTaskPrompt(AgentDeployment $deployment, AgentTask $task): string
    {
        $prompt = "## Task Assignment\n\n";
        $prompt .= "**Title:** {$task->title}\n\n";
        $prompt .= "**Description:** {$task->description}\n\n";

        if (! empty($task->input_data)) {
            $prompt .= "**Input Data:**\n```json\n".json_encode($task->input_data, JSON_PRETTY_PRINT)."\n```\n\n";
        }

        $prompt .= "## Required Output Format\n\n";
        $prompt .= "Respond with a JSON object containing:\n";
        $prompt .= "- `summary`: Brief summary of findings/actions (string)\n";
        $prompt .= "- `result`: Main output/results (object)\n";
        $prompt .= "- `confidence`: Your confidence score 0-100 (number)\n";
        $prompt .= "- `reasoning`: Step-by-step reasoning (string)\n";
        $prompt .= "- `evidence`: Data/sources used (array)\n";
        $prompt .= "- `assumptions`: Any assumptions made (array)\n";
        $prompt .= "- `risks`: Identified risks (array)\n";
        $prompt .= "- `recommendations`: Next steps (array)\n";
        $prompt .= "- `impact_score`: Estimated business impact 0-100 (number)\n\n";
        $prompt .= 'Be thorough, accurate, and transparent about any limitations or uncertainties.';

        return $prompt;
    }

    private function parseTaskOutput(string $content): array
    {
        // Try to extract JSON from response
        if (preg_match('/```json\s*(.*?)\s*```/s', $content, $matches)) {
            $decoded = json_decode($matches[1], true);
            if ($decoded) {
                return $decoded;
            }
        }

        // Try raw JSON
        $decoded = json_decode($content, true);
        if ($decoded) {
            return $decoded;
        }

        // Fallback to text
        return [
            'summary' => substr($content, 0, 500),
            'result' => ['raw_output' => $content],
            'confidence' => 60.0,
            'reasoning' => $content,
            'evidence' => [],
            'assumptions' => [],
            'risks' => [],
            'recommendations' => [],
            'impact_score' => 50,
        ];
    }

    /**
     * Call the AI model with automatic multi-level failover.
     *
     * Attempts each provider in the failover chain in order. Records failures
     * per provider so degraded providers can be skipped on subsequent requests.
     * Falls back to a graceful stub response if all providers fail.
     */
    private function callWithFailover(
        AgentDeployment $deployment,
        string $systemPrompt,
        array $history,
        string $userMessage
    ): array {
        $chain = $this->modelRouter->buildFailoverChain($deployment);
        $lastException = null;

        foreach ($chain as $index => $modelConfig) {
            $provider = $modelConfig['provider'];

            // Skip providers that have exceeded their failure threshold
            if ($this->modelRouter->isProviderDegraded($provider)) {
                Log::info('[AgentOrchestration] Skipping degraded provider', [
                    'provider' => $provider,
                    'deployment_id' => $deployment->id,
                ]);

                continue;
            }

            try {
                $response = $this->circuitBreaker->call(
                    "ai_inference_{$provider}",
                    fn () => $this->callModel($modelConfig, $systemPrompt, $history, $userMessage)
                );

                if ($index > 0) {
                    Log::info('[AgentOrchestration] Failover succeeded', [
                        'deployment_id' => $deployment->id,
                        'provider' => $provider,
                        'model' => $modelConfig['model'],
                        'attempt' => $index + 1,
                    ]);
                }

                return array_merge($response, ['model_used' => $modelConfig['model'], 'provider' => $provider]);

            } catch (\Throwable $e) {
                $lastException = $e;
                $this->modelRouter->recordProviderFailure($provider);

                Log::warning('[AgentOrchestration] Provider failed, trying next', [
                    'deployment_id' => $deployment->id,
                    'provider' => $provider,
                    'model' => $modelConfig['model'],
                    'error' => $e->getMessage(),
                    'attempt' => $index + 1,
                ]);
            }
        }

        // All providers failed — return graceful degradation response
        Log::error('[AgentOrchestration] All providers failed, using fallback', [
            'deployment_id' => $deployment->id,
            'last_error' => $lastException?->getMessage(),
        ]);

        return $this->fallbackResponse($userMessage);
    }

    private function callModel(array $modelConfig, string $systemPrompt, array $history, string $userMessage): array
    {
        // This is a stub — actual implementation connects to the ModelRouterService
        // which routes to OpenAI / Anthropic / Gemini / Ollama based on config
        return [
            'content' => 'Agent response placeholder — model integration active in production.',
            'usage' => ['total_tokens' => 100, 'prompt_tokens' => 80, 'completion_tokens' => 20],
            'cost' => 0.002,
            'finish_reason' => 'stop',
        ];
    }

    /**
     * Graceful degradation response when the AI service is unavailable.
     * Returned by the circuit breaker fallback to keep the application responsive.
     */
    private function fallbackResponse(string $userMessage): array
    {
        return [
            'content' => 'I\'m temporarily unable to process your request — the AI service is '
                .'undergoing maintenance. Please try again in a few minutes.',
            'usage' => ['total_tokens' => 0, 'prompt_tokens' => 0, 'completion_tokens' => 0],
            'cost' => 0,
            'finish_reason' => 'service_unavailable',
            'is_fallback' => true,
        ];
    }

    private function recordUsage(AgentDeployment $deployment, array $response, ?AgentSession $session, ?AgentTask $task = null): void
    {
        UsageRecord::create([
            'organization_id' => $deployment->organization_id,
            'agent_deployment_id' => $deployment->id,
            'metric_type' => 'tokens',
            'quantity' => $response['usage']['total_tokens'] ?? 0,
            'unit_cost' => 0.00002,
            'total_cost' => $response['cost'] ?? 0,
            'model_used' => $response['model'] ?? 'gpt-4o',
            'reference_type' => $session ? 'agent_session' : 'agent_task',
            'reference_id' => $session?->id ?? $task?->id,
            'recorded_date' => now()->toDateString(),
        ]);
    }

    private function createApprovalRequest(
        AgentDeployment $deployment,
        AgentTask $task,
        DecisionLog $decisionLog,
        array $delusionAnalysis
    ): void {
        AgentApproval::create([
            'task_id' => $task->id,
            'agent_deployment_id' => $deployment->id,
            'organization_id' => $deployment->organization_id,
            'requested_from' => $deployment->deployed_by,
            'approval_type' => 'action',
            'title' => "Approval Required: {$task->title}",
            'description' => 'This task requires human approval before proceeding.',
            'proposed_action' => $task->output_data,
            'impact_assessment' => [
                'delusion_risk' => $delusionAnalysis['risk_score'],
                'confidence_score' => $task->confidence_score,
                'risk_level' => $task->risk_score >= 70 ? 'high' : 'medium',
            ],
            'risk_level' => $task->risk_score >= 70 ? 'high' : 'medium',
            'confidence_score' => $task->confidence_score,
            'status' => 'pending',
            'expires_at' => now()->addHours(48),
        ]);
    }

    // ──────────────────────────────────────────────
    // Graph node execution
    // ──────────────────────────────────────────────

    /**
     * Execute a single graph node via agent key.
     *
     * Used by GraphWorkflowEngineService. Looks up an active AgentDeployment
     * by agent key within the workflow's organisation, then runs the task.
     * Returns a normalised result array for the graph engine to route on.
     *
     * @param  string  $agentKey  The Agent::key value
     * @param  array  $context  The accumulated graph context
     * @param  array  $nodeConfig  Per-node overrides (e.g. model, temperature)
     * @param  array  $metadata  Workflow / execution / node identifiers
     * @return array{ status: string, output: array, confidence: float }
     */
    public function executeGraphNode(
        string $agentKey,
        array $context,
        array $nodeConfig = [],
        array $metadata = []
    ): array {
        $organizationId = $metadata['workflow_id']
            ? AgentWorkflow::find($metadata['workflow_id'])?->organization_id
            : null;

        // Resolve deployment by agent key + organisation
        $deployment = AgentDeployment::whereHas(
            'agent', fn ($q) => $q->where('key', $agentKey)
        )
            ->when($organizationId, fn ($q) => $q->where('organization_id', $organizationId))
            ->where('status', 'active')
            ->first();

        if (! $deployment) {
            // No active deployment found — return a graceful skip result
            return [
                'status' => 'skipped',
                'output' => [],
                'confidence' => 0.0,
                'reason' => "No active deployment found for agent key [{$agentKey}]",
            ];
        }

        $task = AgentTask::create([
            'uuid' => (string) Str::uuid(),
            'agent_deployment_id' => $deployment->id,
            'organization_id' => $deployment->organization_id,
            'title' => "Graph node: {$agentKey}",
            'description' => 'Executed as part of graph workflow #'.($metadata['workflow_id'] ?? 'unknown'),
            'task_type' => 'workflow',
            'priority' => 'medium',
            'status' => 'pending',
            'input_data' => $context,
            'metadata' => $metadata,
        ]);

        $completedTask = $this->executeTask($deployment, $task);

        return [
            'status' => $completedTask->status,
            'output' => $completedTask->output_data ?? [],
            'confidence' => (float) ($completedTask->confidence_score ?? 0),
            'task_id' => $completedTask->id,
        ];
    }
}
