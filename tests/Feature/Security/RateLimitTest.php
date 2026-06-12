<?php

namespace Tests\Feature\Security;

use App\Models\AgentDeployment;
use App\Models\AgentSkill;
use App\Models\AgentSkillAssignment;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Rate Limit Tests
 *
 * Verifies that throttle middleware is correctly applied to AI-facing and
 * write-heavy API endpoints.  Hitting the configured limit must return HTTP 429
 * with a Retry-After header so clients can back off gracefully.
 *
 * Rate limiters under test (defined in AppServiceProvider):
 *  - ai-execution  : 10 requests/minute per authenticated user
 *  - api           : 200 requests/minute per user (general API)
 *  - api-writes    : 30 requests/minute per user (POST/PATCH operations)
 */
class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Organization $organization;

    private AgentDeployment $deployment;

    private AgentSkill $skill;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user         = User::factory()->create();
        $this->organization = Organization::factory()->create(['owner_id' => $this->user->id]);
        $this->deployment   = AgentDeployment::factory()->create([
            'organization_id' => $this->organization->id,
        ]);
        $this->skill = AgentSkill::factory()->create();
        $this->token = $this->user->createToken('rate-limit-test')->plainTextToken;

        // Assign the skill to the deployment so the route model binding resolves
        AgentSkillAssignment::factory()->create([
            'agent_deployment_id' => $this->deployment->id,
            'skill_id'            => $this->skill->id,
            'organization_id'     => $this->organization->id,
            'is_enabled'          => true,
        ]);
    }

    // ── ai-execution rate limiter (10 req/min per user) ──────────────────────

    public function test_ai_execution_endpoint_allows_10_requests_per_minute(): void
    {
        // Hit the execute endpoint exactly 10 times — all should get through
        // (they may fail for other reasons like 401/422, but NOT 429)
        for ($i = 1; $i <= 10; $i++) {
            $response = $this->withToken($this->token)
                ->withSession(['current_organization_id' => $this->organization->id])
                ->postJson(
                    "/api/v1/deployments/{$this->deployment->id}/skills/{$this->skill->id}/execute",
                    ['input' => 'test task '.$i]
                );

            $this->assertNotEquals(
                429,
                $response->status(),
                "Request #{$i} was rate-limited before the 10-request limit was reached."
            );
        }
    }

    public function test_ai_execution_endpoint_returns_429_after_10_requests(): void
    {
        // Exhaust the 10-request budget
        for ($i = 0; $i < 10; $i++) {
            $this->withToken($this->token)
                ->withSession(['current_organization_id' => $this->organization->id])
                ->postJson(
                    "/api/v1/deployments/{$this->deployment->id}/skills/{$this->skill->id}/execute",
                    ['input' => 'test task '.$i]
                );
        }

        // The 11th request must be rate-limited
        $response = $this->withToken($this->token)
            ->withSession(['current_organization_id' => $this->organization->id])
            ->postJson(
                "/api/v1/deployments/{$this->deployment->id}/skills/{$this->skill->id}/execute",
                ['input' => 'over-limit task']
            );

        $response->assertStatus(429);
        $this->assertTrue(
            $response->headers->get('Retry-After') !== null
                || $response->headers->get('X-RateLimit-Reset') !== null,
            'HTTP 429 response must include a Retry-After or X-RateLimit-Reset header.'
        );
    }

    public function test_ai_execution_rate_limit_response_includes_retry_after_header(): void
    {
        // Exhaust the 10-request budget first
        for ($i = 0; $i < 10; $i++) {
            $this->withToken($this->token)
                ->withSession(['current_organization_id' => $this->organization->id])
                ->postJson(
                    "/api/v1/deployments/{$this->deployment->id}/skills/{$this->skill->id}/execute",
                    ['input' => 'setup request '.$i]
                );
        }

        // The 429 response must tell the client when to retry
        $response = $this->withToken($this->token)
            ->withSession(['current_organization_id' => $this->organization->id])
            ->postJson(
                "/api/v1/deployments/{$this->deployment->id}/skills/{$this->skill->id}/execute",
                ['input' => 'retry header check']
            );

        $response->assertStatus(429);

        $this->assertTrue(
            $response->headers->get('Retry-After') !== null
                || $response->headers->get('X-RateLimit-Reset') !== null,
            'HTTP 429 response must include a Retry-After or X-RateLimit-Reset header so clients can back off.'
        );
    }

    // ── Deployment write endpoint (throttle:60,1) ─────────────────────────────

    public function test_deployment_store_endpoint_is_rate_limited(): void
    {
        // Exhaust the 60-request deployment budget
        for ($i = 0; $i < 60; $i++) {
            $this->withToken($this->token)
                ->withSession(['current_organization_id' => $this->organization->id])
                ->postJson('/api/v1/deployments', []);
        }

        $response = $this->withToken($this->token)
            ->withSession(['current_organization_id' => $this->organization->id])
            ->postJson('/api/v1/deployments', []);

        $response->assertStatus(429);
    }
}
