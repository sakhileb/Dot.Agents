<?php

namespace App\Http\Requests;

use App\Models\AgentApproval;
use Illuminate\Foundation\Http\FormRequest;

class ProcessApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('review', AgentApproval::class);
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', 'in:approved,rejected,escalated'],
            'reviewer_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
