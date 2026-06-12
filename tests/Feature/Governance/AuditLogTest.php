<?php

namespace Tests\Feature\Governance;

use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\User;
use App\Services\Governance\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    private AuditService $auditService;

    private Organization $org;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->org = Organization::factory()->create();
        $this->user = User::factory()->create();
        $this->auditService = app(AuditService::class);
        $this->actingAs($this->user);
        session(['current_organization_id' => $this->org->id]);
    }

    public function test_log_user_action_creates_audit_log_record(): void
    {
        $log = $this->auditService->logUserAction(
            event: 'organization.settings_updated',
            description: 'Settings were changed',
            data: ['field' => 'name'],
        );

        $this->assertInstanceOf(AuditLog::class, $log);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'organization.settings_updated',
            'event_category' => 'user_action',
            'user_id' => $this->user->id,
        ]);
    }

    public function test_audit_log_captures_organization_context(): void
    {
        $log = $this->auditService->logUserAction(
            event: 'test.org_scoped',
            description: 'Org-scoped audit test',
        );

        $this->assertEquals($this->org->id, $log->organization_id);
    }

    public function test_audit_log_captures_risk_level(): void
    {
        $log = $this->auditService->logUserAction(
            event: 'security.kill_switch.organization',
            description: 'High-risk security event',
        );

        $this->assertNotNull($log->risk_level);
        $this->assertContains($log->risk_level, ['low', 'medium', 'high', 'critical']);
    }

    public function test_audit_log_is_immutable_on_update(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('AuditLog records are immutable');

        $log = AuditLog::factory()->create([
            'organization_id' => $this->org->id,
            'user_id' => $this->user->id,
        ]);

        $log->description = 'tampered';
        $log->save();
    }

    public function test_audit_log_is_immutable_on_delete(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('AuditLog records are immutable');

        $log = AuditLog::factory()->create([
            'organization_id' => $this->org->id,
            'user_id' => $this->user->id,
        ]);

        $log->delete();
    }

    public function test_log_security_event_creates_security_event_record(): void
    {
        $event = $this->auditService->logSecurityEvent(
            organizationId: $this->org->id,
            eventType: 'prompt_injection',
            severity: 'high',
            title: 'Prompt injection detected',
            description: 'User input contained injection attempt',
            data: ['input' => 'ignore previous instructions'],
        );

        $this->assertDatabaseHas('security_events', [
            'organization_id' => $this->org->id,
            'event_type' => 'prompt_injection',
            'severity' => 'high',
            'status' => 'open',
        ]);
        $this->assertNotNull($event->id);
    }

    public function test_audit_logs_are_org_scoped(): void
    {
        $otherOrg = Organization::factory()->create();

        AuditLog::factory()->create(['organization_id' => $this->org->id]);
        AuditLog::factory()->create(['organization_id' => $otherOrg->id]);

        // Scope is active — only current org logs are returned
        $logs = AuditLog::all();
        $this->assertTrue($logs->every(fn ($l) => $l->organization_id === $this->org->id));
    }

    public function test_high_risk_events_are_flagged(): void
    {
        $log = $this->auditService->logUserAction(
            event: 'security.kill_switch.deployment',
            description: 'Kill switch activated',
        );

        $this->assertEquals('critical', $log->risk_level);
    }

    public function test_audit_log_stores_metadata(): void
    {
        $metadata = ['old' => ['name' => 'Acme'], 'new' => ['name' => 'Acme Inc']];

        $log = $this->auditService->logUserAction(
            event: 'organization.settings_updated',
            description: 'Name updated',
            data: $metadata,
        );

        $fresh = $log->fresh();
        $this->assertEquals($metadata, $fresh->new_values);
    }
}
