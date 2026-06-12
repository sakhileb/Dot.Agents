<?php

namespace Tests\Feature\Actions\Compliance;

use App\Actions\Compliance\EraseUserDataAction;
use App\Actions\Compliance\ExportUserDataAction;
use App\Actions\Compliance\RecordConsentAction;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class ComplianceActionsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->organization = Organization::factory()->create(['owner_id' => $this->user->id]);
        session(['current_organization_id' => $this->organization->id]);
        Gate::before(fn () => true);
    }

    // ── RecordConsentAction ────────────────────────────────────────────────

    public function test_records_granted_consent(): void
    {
        $this->actingAs($this->user);

        app(RecordConsentAction::class)->execute(
            user: $this->user,
            consentPurpose: 'marketing_emails',
            granted: true,
            version: '1.0',
            ipAddress: '127.0.0.1',
        );

        $this->user->refresh();
        $records = $this->user->consent_records;
        $this->assertTrue($records['marketing_emails']['granted']);
        $this->assertSame('1.0', $records['marketing_emails']['version']);
    }

    public function test_records_withdrawn_consent(): void
    {
        $this->actingAs($this->user);

        app(RecordConsentAction::class)->execute(
            user: $this->user,
            consentPurpose: 'analytics',
            granted: false,
            version: '1.0',
        );

        $this->user->refresh();
        $this->assertFalse($this->user->consent_records['analytics']['granted']);
    }

    public function test_has_consent_returns_true_when_granted(): void
    {
        $action = app(RecordConsentAction::class);
        $this->actingAs($this->user);

        $action->execute($this->user, 'marketing_emails', true, '1.0');
        $this->user->refresh();

        $this->assertTrue($action->hasConsent($this->user, 'marketing_emails'));
    }

    public function test_has_consent_returns_false_when_withdrawn(): void
    {
        $action = app(RecordConsentAction::class);
        $this->actingAs($this->user);

        $action->execute($this->user, 'analytics', false, '1.0');
        $this->user->refresh();

        $this->assertFalse($action->hasConsent($this->user, 'analytics'));
    }

    public function test_consent_creates_audit_log(): void
    {
        $this->actingAs($this->user);
        session(['current_organization_id' => $this->organization->id]);

        app(RecordConsentAction::class)->execute($this->user, 'marketing_emails', true, '1.0');

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'compliance.consent_granted',
        ]);
    }

    // ── ExportUserDataAction ───────────────────────────────────────────────

    public function test_export_returns_complete_data_structure(): void
    {
        $this->actingAs($this->user);
        session(['current_organization_id' => $this->organization->id]);

        $data = app(ExportUserDataAction::class)->execute($this->user, $this->user);

        $this->assertArrayHasKey('exported_at', $data);
        $this->assertArrayHasKey('subject', $data);
        $this->assertArrayHasKey('profile', $data);
        $this->assertArrayHasKey('organizations', $data);
        $this->assertArrayHasKey('audit_activity', $data);
        $this->assertSame($this->user->id, $data['subject']['id']);
        $this->assertSame($this->user->email, $data['subject']['email']);
    }

    public function test_export_creates_audit_log(): void
    {
        $this->actingAs($this->user);
        session(['current_organization_id' => $this->organization->id]);

        app(ExportUserDataAction::class)->execute($this->user, $this->user);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'compliance.data_exported',
        ]);
    }

    // ── EraseUserDataAction ────────────────────────────────────────────────

    public function test_erase_pseudonymises_user_email(): void
    {
        $this->actingAs($this->user);
        session(['current_organization_id' => $this->organization->id]);

        app(EraseUserDataAction::class)->execute($this->user, $this->user);

        $this->user->refresh();
        $this->assertStringContainsString('erased-', $this->user->email);
        $this->assertStringContainsString('@deleted.dotagents.com', $this->user->email);
    }

    public function test_erase_sets_erased_at_timestamp(): void
    {
        $this->actingAs($this->user);
        session(['current_organization_id' => $this->organization->id]);

        app(EraseUserDataAction::class)->execute($this->user, $this->user);

        $this->user->refresh();
        $this->assertNotNull($this->user->erased_at);
    }

    public function test_erase_revokes_api_tokens(): void
    {
        $this->actingAs($this->user);
        session(['current_organization_id' => $this->organization->id]);
        $this->user->createToken('test-token');
        $this->assertCount(1, $this->user->tokens);

        app(EraseUserDataAction::class)->execute($this->user, $this->user);

        $this->assertCount(0, $this->user->fresh()->tokens);
    }

    public function test_erase_creates_audit_log(): void
    {
        $this->actingAs($this->user);
        session(['current_organization_id' => $this->organization->id]);

        app(EraseUserDataAction::class)->execute($this->user, $this->user);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'compliance.data_erased',
        ]);
    }
}
