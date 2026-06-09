<?php

namespace App\Mail;

use App\Models\AgentDeployment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DeploymentAlertEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly AgentDeployment $deployment,
        public readonly string $alertType,
        public readonly string $alertMessage,
        public readonly array $context = [],
    ) {}

    public function envelope(): Envelope
    {
        $prefix = match ($this->alertType) {
            'paused' => '[Paused]',
            'decommissioned' => '[Decommissioned]',
            'error' => '[Error]',
            'drift_detected' => '[Drift Detected]',
            default => '[Alert]',
        };

        return new Envelope(
            subject: "{$prefix} Agent Deployment — {$this->deployment->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.deployment-alert',
            with: [
                'deploymentName' => $this->deployment->name,
                'alertType' => $this->alertType,
                'alertMessage' => $this->alertMessage,
                'context' => $this->context,
                'deploymentUrl' => url("/deployments/{$this->deployment->uuid}"),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
