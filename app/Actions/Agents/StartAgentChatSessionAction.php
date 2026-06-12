<?php

namespace App\Actions\Agents;

use App\Events\AgentChatStarted;
use App\Models\AgentDeployment;
use App\Models\AgentMessage;
use App\Models\AgentSession;
use App\Services\Governance\AuditService;
use Illuminate\Support\Facades\Gate;

class StartAgentChatSessionAction
{
    public function __construct(private readonly AuditService $auditService) {}

    /**
     * Create a new chat session for the given deployment.
     */
    public function execute(AgentDeployment $deployment, int $userId, ?int $organizationId): AgentSession
    {
        Gate::authorize('chat', $deployment);

        $session = AgentSession::create([
            'agent_deployment_id' => $deployment->id,
            'organization_id' => $organizationId ?? $deployment->organization_id,
            'user_id' => $userId,
            'session_type' => 'conversation',
            'title' => 'New Conversation',
            'status' => 'active',
            'started_at' => now(),
        ]);

        event(new AgentChatStarted($session));

        return $session;
    }

    /**
     * Store a user message in the session.
     */
    public function storeUserMessage(AgentSession $session, string $content): AgentMessage
    {
        $message = AgentMessage::create([
            'session_id' => $session->id,
            'role' => 'user',
            'content' => $content,
        ]);

        $session->increment('message_count');

        return $message;
    }

    /**
     * Close a session and mark it as completed.
     */
    public function endSession(AgentSession $session): void
    {
        $session->update([
            'status' => 'completed',
            'ended_at' => now(),
        ]);
    }
}
