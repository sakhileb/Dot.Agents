<?php

namespace App\Services\AI;

use App\Models\AgentDeployment;

class ModelRouterService
{
    /**
     * Route to the optimal model for a deployment.
     * Supports OpenAI, Anthropic, Gemini, Ollama.
     */
    public function resolve(AgentDeployment $deployment): array
    {
        $override = $deployment->model_override;
        $agent = $deployment->agent;

        $model = $override ?? $agent->primary_model ?? 'gpt-4o';
        $provider = $this->detectProvider($model);

        return array_merge(
            $this->getProviderDefaults($provider),
            [
                'model' => $model,
                'provider' => $provider,
                'temperature' => $deployment->agent->defaultPersona?->temperature ?? 0.7,
                'max_tokens' => $deployment->agent->defaultPersona?->max_tokens ?? 4096,
            ],
            $deployment->model_config_override ?? []
        );
    }

    private function detectProvider(string $model): string
    {
        return match (true) {
            str_starts_with($model, 'gpt-') || str_starts_with($model, 'o1') => 'openai',
            str_starts_with($model, 'claude-') => 'anthropic',
            str_starts_with($model, 'gemini-') => 'google',
            str_starts_with($model, 'llama') || str_starts_with($model, 'mistral') => 'ollama',
            default => 'openai',
        };
    }

    private function getProviderDefaults(string $provider): array
    {
        return match ($provider) {
            'openai' => [
                'api_key' => config('openai.api_key'),
                'base_url' => 'https://api.openai.com/v1',
            ],
            'anthropic' => [
                'api_key' => config('services.anthropic.key'),
                'base_url' => 'https://api.anthropic.com/v1',
            ],
            'google' => [
                'api_key' => config('services.gemini.key'),
                'base_url' => 'https://generativelanguage.googleapis.com/v1beta',
            ],
            'ollama' => [
                'api_key' => null,
                'base_url' => config('services.ollama.url', 'http://localhost:11434'),
            ],
            default => [],
        };
    }
}
