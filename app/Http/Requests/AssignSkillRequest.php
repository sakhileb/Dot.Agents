<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignSkillRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled via Gate::authorize('update', $deployment) in controller
    }

    public function rules(): array
    {
        return [
            'skill_id' => ['required', 'integer', 'exists:agent_skills,id'],
            'is_enabled' => ['boolean'],
            'config' => ['nullable', 'array'],
        ];
    }
}
