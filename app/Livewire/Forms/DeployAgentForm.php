<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use Livewire\Attributes\Validate;
use Livewire\Form;

class DeployAgentForm extends Form
{
    #[Validate('required|string|max:100')]
    public string $deploymentName = '';

    #[Validate('required|in:advisory,semi-autonomous,autonomous,executive_approval')]
    public string $deploymentMode = 'advisory';

    #[Validate('nullable|string|max:5000')]
    public ?string $customInstructions = null;

    #[Validate('nullable|integer|exists:departments,id')]
    public ?int $departmentId = null;

    #[Validate('nullable|integer|exists:teams,id')]
    public ?int $teamId = null;

    #[Validate('nullable|numeric|min:0|max:100')]
    public ?float $confidenceThreshold = 75.0;

    public function toArray(): array
    {
        return [
            'deployment_name' => $this->deploymentName,
            'deployment_mode' => $this->deploymentMode,
            'custom_instructions' => $this->customInstructions,
            'department_id' => $this->departmentId,
            'team_id' => $this->teamId,
            'confidence_threshold' => $this->confidenceThreshold,
        ];
    }
}
