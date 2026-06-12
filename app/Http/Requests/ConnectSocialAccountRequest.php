<?php

namespace App\Http\Requests;

use App\Models\SocialAccount;
use Illuminate\Foundation\Http\FormRequest;

class ConnectSocialAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', [SocialAccount::class, session('current_organization_id')]);
    }

    public function rules(): array
    {
        return [
            'agent_deployment_id' => ['nullable', 'integer', 'exists:agent_deployments,id'],
        ];
    }
}
