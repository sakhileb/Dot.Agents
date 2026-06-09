<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use Livewire\Attributes\Validate;
use Livewire\Form;

class DeployAgentForm extends Form
{
    #[Validate('required|string|max:100')]
    public string $deployment_name = '';

    #[Validate('required|in:advisory,semi-autonomous,autonomous,executive_approval')]
    public string $deployment_mode = 'advisory';

    #[Validate('nullable|string|max:5000')]
    public ?string $custom_instructions = null;

    #[Validate('nullable|integer|exists:departments,id')]
    public ?int $department_id = null;

    #[Validate('nullable|integer|exists:teams,id')]
    public ?int $team_id = null;

    #[Validate('nullable|numeric|min:0|max:100')]
    public ?float $confidence_threshold = 75.0;

    public function toArray(): array
    {
        return [
            'deployment_name' => $this->deployment_name,
            'deployment_mode' => $this->deployment_mode,
            'custom_instructions' => $this->custom_instructions,
            'department_id' => $this->department_id,
            'team_id' => $this->team_id,
            'confidence_threshold' => $this->confidence_threshold,
        ];
    }
}
