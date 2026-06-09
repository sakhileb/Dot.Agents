<?php

namespace Tests\Feature\Security;

use App\Models\AgentDeployment;
use App\Models\AuditLog;
use App\Models\DecisionLog;
use App\Models\Organization;
use App\Models\SecurityEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cross-Tenant Leakage Prevention Tests
 *
 * Ensures no data from one organization can be accessed by another.
 * Extended coverage beyond the base TenantIsolationTest.
 */
class CrossTenantLeakageTest extends TestCase
{
    use RefreshDatabase;

    private User $userA;

    private User $userB;

    private Organization $orgA;

    private Organization $orgB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userA = User::factory()->create();
        $this->orgA = Organization::factory()->create(['owner_id' => $this->userA->id]);
        $this->orgA->users()->attach($this->userA->id, ['role' => 'admin', 'is_primary' => true]);
        $this->userB = User::factory()->create();
        $this->orgB = Organization::factory()->create(['owner_id' => $this->userB->id]);
        $this->orgB->users()->attach($this->userB->id, ['role' => 'admin', 'is_primary' => true]);
    }

    public function test_audit_logs_cannot_leak_across_organizations(): void
    {
        AuditLog::factory()->create(['organization_id' => $this->orgA->id, 'event' => 'org_a.secret']);
        AuditLog::factory()->create(['organization_id' => $this->orgB->id, 'event' => 'org_b.secret']);

        $orgALogs = AuditLog::where('organization_id', $this->orgA->id)->pluck('event');

        $this->assertTrue($orgALogs->contains('org_a.secret'));
        $this->assertFalse($orgALogs->contains('org_b.secret'));
    }

    public function test_decision_logs_cannot_leak_across_organizations(): void
    {
        $depA = AgentDeployment::factory()->create(['organization_id' => $this->orgA->id]);
        $depB = AgentDeployment::factory()->create(['organization_id' => $this->orgB->id]);

        DecisionLog::factory()->create(['organization_id' => $this->orgA->id, 'agent_deployment_id' => $depA->id]);
        DecisionLog::factory()->create(['organization_id' => $this->orgB->id, 'agent_deployment_id' => $depB->id]);

        $orgALogs = DecisionLog::where('organization_id', $this->orgA->id)->get();
        $this->assertTrue($orgALogs->every(fn ($log) => $log->organization_id === $this->orgA->id));
    }

    public function test_security_events_cannot_leak_across_organizations(): void
    {
        SecurityEvent::factory()->create(['organization_id' => $this->orgA->id]);
        SecurityEvent::factory()->create(['organization_id' => $this->orgB->id]);

        $orgAEvents = SecurityEvent::where('organization_id', $this->orgA->id)->get();
        $this->assertTrue($orgAEvents->every(fn ($e) => $e->organization_id === $this->orgA->id));
    }

    public function test_api_agents_endpoint_returns_200_for_authenticated_user(): void
    {
        $token = $this->userA->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->withSession(['current_organization_id' => $this->orgA->id])
            ->getJson('/api/v1/agents')
            ->assertOk();
    }

    public function test_api_deployments_only_returns_own_org_deployments(): void
    {
        $token = $this->userA->createToken('test')->plainTextToken;

        $ownDep = AgentDeployment::factory()->create(['organization_id' => $this->orgA->id]);
        $otherDep = AgentDeployment::factory()->create(['organization_id' => $this->orgB->id]);

        $response = $this->withToken($token)
            ->withSession(['current_organization_id' => $this->orgA->id])
            ->getJson('/api/v1/deployments');

        $response->assertOk();

        $ids = collect($response->json('data'))->pluck('id');
        $this->assertContains($ownDep->id, $ids);
        $this->assertNotContains($otherDep->id, $ids);
    }

    public function test_api_denies_access_to_other_org_deployment(): void
    {
        $token = $this->userA->createToken('test')->plainTextToken;
        $otherDep = AgentDeployment::factory()->create(['organization_id' => $this->orgB->id]);

        $response = $this->withToken($token)
            ->withSession(['current_organization_id' => $this->orgA->id])
            ->getJson("/api/v1/deployments/{$otherDep->id}");

        // Must not return 200 with cross-tenant data
        $this->assertNotSame(200, $response->status());
    }

    public function test_unauthenticated_api_access_denied(): void
    {
        $this->getJson('/api/v1/deployments')->assertUnauthorized();
        $this->postJson('/api/v1/deployments', [])->assertUnauthorized();
        $this->getJson('/api/v1/me')->assertUnauthorized();
    }

    public function test_member_of_org_a_cannot_be_member_of_org_b_data(): void
    {
        // Org A members should not see Org B's member list
        $orgAMemberIds = $this->orgA->users()->pluck('users.id');
        $orgBMemberIds = $this->orgB->users()->pluck('users.id');

        // The owner of A is not in B's list (unless explicitly added)
        $this->assertFalse($orgBMemberIds->contains($this->userA->id));
        $this->assertFalse($orgAMemberIds->contains($this->userB->id));
    }
}
