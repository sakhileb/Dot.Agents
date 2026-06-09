<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ActivateSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'plan_id' => ['required', 'integer', 'exists:subscription_plans,id'],
            'billing_period' => ['sometimes', 'required', 'string', 'in:monthly,annual'],
        ];
    }

    public function messages(): array
    {
        return [
            'plan_id.exists' => 'The selected subscription plan does not exist or is inactive.',
        ];
    }
}
