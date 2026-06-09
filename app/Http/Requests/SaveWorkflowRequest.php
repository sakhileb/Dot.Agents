<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveWorkflowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'nodes' => ['required', 'array', 'min:1'],
            'nodes.*.id' => ['sometimes', 'nullable', 'string', 'max:36'],
            'nodes.*.agent_key' => ['required', 'string', 'max:100'],
            'nodes.*.label' => ['sometimes', 'nullable', 'string', 'max:255'],
            'nodes.*.x' => ['sometimes', 'numeric'],
            'nodes.*.y' => ['sometimes', 'numeric'],
            'nodes.*.config' => ['sometimes', 'nullable', 'array'],
            'connections' => ['required', 'array'],
            'connections.*.id' => ['sometimes', 'nullable', 'string', 'max:36'],
            'connections.*.from' => ['required', 'string', 'max:36'],
            'connections.*.to' => ['required', 'string', 'max:36'],
            'connections.*.condition' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'connections.*.label' => ['sometimes', 'nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'nodes.required' => 'A workflow must have at least one node.',
            'nodes.*.agent_key.required' => 'Each node must specify an agent_key.',
            'connections.*.from.required' => 'Each connection must specify a source node.',
            'connections.*.to.required' => 'Each connection must specify a target node.',
        ];
    }
}
