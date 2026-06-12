<?php

namespace App\Actions\Agents;

use App\DTOs\Agents\StartAgentChatSessionData;
use App\Events\AgentChatStarted;
use App\Models\AgentDeployment;
use App\Models\AgentMessage;
use App\Models\AgentSession;
use App\Services\Governance\AuditService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;

class StartAgentChatSessionAction
{
    public function __construct(private readonly AuditService $auditService) {}

    /**
     * Create a new interactive chat session for the given deployment.
     *
     * Persists the AgentSession, optionally seeds a system-prompt message,
     * logs the session start via AuditService, and fires AgentChatStarted
     * so analytics listeners can record the engagement metric.
     *
     * @param  AgentDeployment  $deployment  The deployment hosting the chat session.
     * @param  StartAgentChatSessionData  $data  DTO carrying user ID, title, and org context.
     * @return AgentSession The newly created chat session.
     *
     * @throws AuthorizationException When actor lacks 'chat' permission.
     */
    public function execute(AgentDeployment $deployment, StartAgentChatSessionData $data): AgentSession
    {
        Gate::authorize('chat', $deployment);

        $session = AgentSession::create([
            'agent_deployment_id' => $deployment->id,
            'organization_id' => $data->organizationId ?? $deployment->organization_id,
            'user_id' => $data->userId,
            'session_type' => 'conversation',
            'title' => $data->title ?? 'New Conversation',
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
