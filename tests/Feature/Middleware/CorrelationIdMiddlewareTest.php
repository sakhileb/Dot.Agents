<?php

namespace Tests\Feature\Middleware;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CorrelationIdMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_correlation_id_is_generated_and_echoed_in_response(): void
    {
        $response = $this->get('/up');

        $response->assertHeader('X-Correlation-ID');

        $correlationId = $response->headers->get('X-Correlation-ID');
        $this->assertNotEmpty($correlationId);
        // Must be a valid UUID
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $correlationId
        );
    }

    public function test_provided_correlation_id_is_preserved(): void
    {
        $correlationId = 'test-correlation-id-12345';

        $response = $this->withHeader('X-Correlation-ID', $correlationId)->get('/up');

        $response->assertHeader('X-Correlation-ID', $correlationId);
    }

    public function test_x_request_id_is_accepted_as_fallback(): void
    {
        $requestId = 'fallback-request-id-9876';

        $response = $this->withHeader('X-Request-ID', $requestId)->get('/up');

        $response->assertHeader('X-Correlation-ID', $requestId);
    }

    public function test_each_request_without_header_gets_unique_id(): void
    {
        $response1 = $this->get('/up');
        $response2 = $this->get('/up');

        $id1 = $response1->headers->get('X-Correlation-ID');
        $id2 = $response2->headers->get('X-Correlation-ID');

        $this->assertNotSame($id1, $id2);
    }
}
