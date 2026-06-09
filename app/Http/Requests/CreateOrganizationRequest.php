<?php

namespace App\Http\Requests;

use App\DTOs\Organizations\CreateOrganizationData;
use Illuminate\Foundation\Http\FormRequest;

class CreateOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'industry' => ['required', 'string', 'max:100'],
            'size' => ['required', 'in:1-10,11-50,51-200,201-500,501-1000,1000+'],
            'domain' => ['nullable', 'string', 'max:253'],
        ];
    }

    public function toOrganizationData(): CreateOrganizationData
    {
        return CreateOrganizationData::fromArray($this->validated());
    }
}
