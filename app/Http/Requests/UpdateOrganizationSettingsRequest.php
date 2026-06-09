<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrganizationSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'domain' => ['sometimes', 'nullable', 'string', 'max:255', 'regex:/^[a-z0-9.-]+$/i'],
            'logo' => ['sometimes', 'nullable', 'url', 'max:2048'],
            'industry' => ['sometimes', 'nullable', 'string', 'max:100'],
            'size' => ['sometimes', 'nullable', 'string', 'in:1-10,11-50,51-200,201-500,500+'],
            'country' => ['sometimes', 'nullable', 'string', 'size:2'],
            'timezone' => ['sometimes', 'nullable', 'string', 'max:100'],
            'currency' => ['sometimes', 'nullable', 'string', 'size:3'],
            'settings' => ['sometimes', 'nullable', 'array'],
            'billing_address' => ['sometimes', 'nullable', 'array'],
            'billing_address.line1' => ['sometimes', 'nullable', 'string', 'max:255'],
            'billing_address.city' => ['sometimes', 'nullable', 'string', 'max:100'],
            'billing_address.country' => ['sometimes', 'nullable', 'string', 'size:2'],
        ];
    }
}
