<?php

namespace App\Services\Infrastructure;

use App\Models\Webhook;
use App\Models\WebhookDelivery;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * WebhookService
 *
 * Dispatches outgoing webhook notifications to subscribed endpoints.
 * Signs payloads with HMAC-SHA256 and records every delivery attempt.
 */
class WebhookService
{
    /**
     * Dispatch an event to all active webhooks subscribed to it.
     *
     * @param  int  $organizationId  Scope deliveries to this org
     * @param  string  $event  e.g. 'agent.deployed', 'task.completed'
     * @param  array  $payload  Event data to send as JSON body
     */
    public function dispatch(int $organizationId, string $event, array $payload): void
    {
        $webhooks = Webhook::withoutGlobalScope('organization')
            ->where('organization_id', $organizationId)
            ->where('is_active', true)
            ->get();

        foreach ($webhooks as $webhook) {
            if ($webhook->listensTo($event)) {
                $this->deliver($webhook, $event, $payload);
            }
        }
    }

    /**
     * Deliver a single webhook notification with HMAC signature.
     */
    private function deliver(Webhook $webhook, string $event, array $payload): void
    {
        $body = json_encode([
            'event' => $event,
            'occurred_at' => now()->toIso8601String(),
            'organization_id' => $webhook->organization_id,
            'data' => $payload,
        ]);

        $signature = $webhook->sign($body);
        $start = microtime(true);

        $delivery = WebhookDelivery::create([
            'webhook_id' => $webhook->id,
            'organization_id' => $webhook->organization_id,
            'event' => $event,
            'payload' => $payload,
            'status' => 'pending',
            'attempt' => 1,
        ]);

        try {
            $response = Http::timeout($webhook->timeout_seconds)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Signature-256' => "sha256={$signature}",
                    'X-Event' => $event,
                    'X-Delivery' => (string) $delivery->id,
                ])
                ->send('POST', $webhook->url, ['body' => $body]);

            $durationMs = (int) ((microtime(true) - $start) * 1000);

            $delivery->update([
                'status' => $response->successful() ? 'delivered' : 'failed',
                'http_status' => $response->status(),
                'response_body' => substr((string) $response->body(), 0, 2000),
                'duration_ms' => $durationMs,
                'delivered_at' => $response->successful() ? now() : null,
                'error_message' => $response->successful() ? null : "HTTP {$response->status()}",
            ]);

            $webhook->update(['last_triggered_at' => now()]);
        } catch (Throwable $e) {
            $durationMs = (int) ((microtime(true) - $start) * 1000);

            $delivery->update([
                'status' => 'failed',
                'duration_ms' => $durationMs,
                'error_message' => $e->getMessage(),
            ]);

            Log::warning('WebhookService: delivery failed', [
                'webhook_id' => $webhook->id,
                'event' => $event,
                'url' => $webhook->url,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
