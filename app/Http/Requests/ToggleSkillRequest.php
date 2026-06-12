<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ToggleSkillRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled via Gate::authorize('update', $deployment) in controller
    }

    public function rules(): array
    {
        return [
            'is_enabled' => ['required', 'boolean'],
        ];
    }
}
