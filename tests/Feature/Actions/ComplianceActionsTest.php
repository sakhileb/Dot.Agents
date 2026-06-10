<?php

namespace Tests\Feature\Actions;

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
        // Set org context so AuditService can resolve organization_id from session
        session(['current_organization_id' => $this->organization->id]);
    }

    public function test_record_consent_stores_consent_record(): void
    {
        $this->actingAs($this->user);
        Gate::before(fn () => true);

        app(RecordConsentAction::class)->execute(
            user: $this->user,
            consentPurpose: 'marketing_emails',
            granted: true,
            version: '1.0',
            ipAddress: '127.0.0.1',
        );

        $this->user->refresh();
        $consents = $this->user->consent_records ?? [];

        $this->assertArrayHasKey('marketing_emails', $consents);
        $this->assertTrue($consents['marketing_emails']['granted']);
        $this->assertEquals('1.0', $consents['marketing_emails']['version']);
    }

    public function test_record_consent_creates_audit_log_entry(): void
    {
        $this->actingAs($this->user);

        app(RecordConsentAction::class)->execute(
            user: $this->user,
            consentPurpose: 'data_processing',
            granted: true,
            version: '2.1',
        );

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->user->id,
            'event' => 'compliance.consent_granted',
        ]);
    }

    public function test_erase_user_data_pseudonymises_user(): void
    {
        $this->actingAs($this->user);
        Gate::before(fn () => true);

        app(EraseUserDataAction::class)->execute($this->user, $this->user);

        $this->user->refresh();
        $this->assertStringStartsWith('Erased User #', $this->user->name);
        $this->assertStringContainsString('@deleted.dotagents.com', $this->user->email);
    }

    public function test_export_user_data_returns_array_with_personal_data(): void
    {
        $this->actingAs($this->user);
        Gate::before(fn () => true);

        $export = app(ExportUserDataAction::class)->execute($this->user, $this->user);

        $this->assertIsArray($export);
        $this->assertArrayHasKey('subject', $export);
        $this->assertEquals($this->user->email, $export['subject']['email']);
    }
}
