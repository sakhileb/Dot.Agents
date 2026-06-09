<?php

namespace Tests\Feature\Security;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_headers_present_on_every_response(): void
    {
        $response = $this->get('/');

        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_content_security_policy_header_is_set(): void
    {
        $response = $this->get('/');
        $this->assertNotEmpty($response->headers->get('Content-Security-Policy'));
    }

    public function test_server_header_is_removed(): void
    {
        $response = $this->get('/');
        $this->assertEmpty($response->headers->get('X-Powered-By', ''));
    }

    public function test_permissions_policy_is_set(): void
    {
        $response = $this->get('/');
        $permissions = $response->headers->get('Permissions-Policy', '');

        // Should disable camera and microphone
        $this->assertStringContainsString('camera=()', $permissions);
        $this->assertStringContainsString('microphone=()', $permissions);
    }
}
