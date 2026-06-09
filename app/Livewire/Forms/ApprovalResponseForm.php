<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use Livewire\Attributes\Validate;
use Livewire\Form;

class ApprovalResponseForm extends Form
{
    #[Validate('required|in:approved,rejected')]
    public string $decision = '';

    #[Validate('nullable|string|max:2000')]
    public ?string $notes = null;

    #[Validate('nullable|array')]
    public ?array $conditions = null;

    #[Validate('required_if:decision,rejected|nullable|string|max:500')]
    public ?string $rejectionReason = null;

    public function toArray(): array
    {
        return [
            'decision' => $this->decision,
            'notes' => $this->notes,
            'conditions' => $this->conditions,
            'rejection_reason' => $this->rejectionReason,
        ];
    }

    public function isApproval(): bool
    {
        return $this->decision === 'approved';
    }
}
