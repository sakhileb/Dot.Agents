<?php

namespace App\Http\Requests;

use App\Services\Governance\AuditService;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDeploymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:100'],
            'deployment_mode' => ['sometimes', 'in:advisory,semi-autonomous,autonomous,executive_approval'],
            'confidence_threshold' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'custom_instructions' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'status' => ['sometimes', 'in:active,paused'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $instructions = $this->input('custom_instructions');
            if ($instructions && app(AuditService::class)->detectPromptInjection($instructions)) {
                $validator->errors()->add('custom_instructions', 'Custom instructions contain disallowed content.');
            }
        });
    }
}
