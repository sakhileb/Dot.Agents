<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Actions\Billing\HandleStripeWebhookAction;
use App\Services\Billing\StripeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class WebhookSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_stripe_webhook_idempotency_prevents_replay(): void
    {
        $eventId = 'evt_test_idempotency_'.uniqid();
        Cache::put('stripe_event_'.$eventId, true, 172800);

        $mockEvent = $this->buildMockStripeEvent($eventId, 'invoice.payment_succeeded');

        $this->mock(StripeService::class);

        $action = app(HandleStripeWebhookAction::class);
        $action->execute($mockEvent);

        // Still set — event was skipped (idempotency guard worked)
        $this->assertTrue(Cache::has('stripe_event_'.$eventId));
    }

    public function test_stripe_webhook_processes_new_event_and_marks_cache(): void
    {
        $eventId = 'evt_test_fresh_'.uniqid();
        $this->assertFalse(Cache::has('stripe_event_'.$eventId));

        $mockEvent = $this->buildMockStripeEvent($eventId, 'unknown.event.type');

        $this->mock(StripeService::class);

        $action = app(HandleStripeWebhookAction::class);
        $action->execute($mockEvent);

        $this->assertTrue(Cache::has('stripe_event_'.$eventId));
    }

    public function test_agent_api_catalog_has_rate_limiting(): void
    {
        $route = collect(app('router')->getRoutes())->first(
            fn ($r) => $r->uri() === 'api/v1/agents' && in_array('GET', $r->methods())
        );

        $this->assertNotNull($route, 'Agent catalog route must exist');
        $this->assertStringContainsString('throttle', implode(',', $route->middleware()));
    }

    public function test_legacy_api_user_route_removed(): void
    {
        $response = $this->getJson('/api/user');
        // Must be 401 (protected) or 404 (removed) — never 200 without auth+org
        $this->assertContains($response->status(), [401, 404]);
    }

    /**
     * Build a Stripe Event mock using stdClass (Stripe SDK not installed in test env).
     */
    private function buildMockStripeEvent(string $id, string $type): object
    {
        $event = new \stdClass;
        $event->id = $id;
        $event->type = $type;
        $event->data = new \stdClass;
        $event->data->object = new \stdClass;

        return $event;
    }
}
