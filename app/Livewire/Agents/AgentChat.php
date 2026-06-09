<?php

namespace App\Livewire\Agents;

use App\Actions\Agents\StartAgentChatSessionAction;
use App\Models\AgentDeployment;
use App\Models\AgentMessage;
use App\Models\AgentSession;
use App\Services\AI\AgentOrchestrationService;
use App\Services\Governance\AuditService;
use Livewire\Attributes\Computed;
use Livewire\Component;

class AgentChat extends Component
{
    public int $deploymentId;

    public ?int $sessionId = null;

    public string $message = '';

    public bool $isTyping = false;

    public bool $showTaskPanel = false;

    public function mount(int $deploymentId): void
    {
        $this->deploymentId = $deploymentId;
        $this->initSession();
    }

    #[Computed]
    public function deployment(): AgentDeployment
    {
        return AgentDeployment::with('agent')->findOrFail($this->deploymentId);
    }

    #[Computed]
    public function session(): ?AgentSession
    {
        return $this->sessionId ? AgentSession::find($this->sessionId) : null;
    }

    #[Computed]
    public function chatMessages()
    {
        if (! $this->sessionId) {
            return collect();
        }

        return AgentMessage::where('session_id', $this->sessionId)
            ->orderBy('created_at')
            ->get();
    }

    public function sendMessage(): void
    {
        $this->validate(['message' => 'required|string|max:10000']);

        $deployment = $this->deployment;
        $session = $this->session;
        $userMessage = $this->message;
        $this->message = '';

        // Security check: prompt injection detection
        $auditService = app(AuditService::class);
        if ($auditService->detectPromptInjection($userMessage, $deployment)) {
            $this->dispatch('security-alert', message: 'Suspicious input detected and logged.');

            return;
        }

        // Store user message via Action
        $chatAction = app(StartAgentChatSessionAction::class);
        $chatAction->storeUserMessage($session, $userMessage);

        // Process through orchestration service
        $this->isTyping = true;

        try {
            $orchestrator = app(AgentOrchestrationService::class);
            $orchestrator->processMessage($deployment, $session, $userMessage);
        } finally {
            $this->isTyping = false;
        }

        // Refresh messages
        unset($this->messages);
    }

    public function newSession(): void
    {
        $this->sessionId = null;
        $this->initSession();
        unset($this->messages, $this->session);
    }

    private function initSession(): void
    {
        $deployment = AgentDeployment::findOrFail($this->deploymentId);
        $session = app(StartAgentChatSessionAction::class)->execute(
            $deployment,
            auth()->id(),
            session('current_organization_id'),
        );

        $this->sessionId = $session->id;
    }

    public function endSession(): void
    {
        if ($this->sessionId) {
            $session = AgentSession::find($this->sessionId);
            if ($session) {
                app(StartAgentChatSessionAction::class)->endSession($session);
            }
        }

        $this->redirect(route('agents.deployments'));
    }

    public function render()
    {
        return view('livewire.agents.agent-chat');
    }
}
