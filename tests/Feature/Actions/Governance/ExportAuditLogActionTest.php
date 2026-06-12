<?php

namespace Tests\Feature\Actions\Governance;

use App\Actions\Governance\ExportAuditLogAction;
use App\DTOs\Governance\AuditLogExportParams;
use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class ExportAuditLogActionTest extends TestCase
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

    public function test_returns_streamed_response_for_csv(): void
    {
        $this->actingAs($this->user);

        AuditLog::factory()->count(3)->create(['organization_id' => $this->organization->id]);

        $params = new AuditLogExportParams(
            organizationId: $this->organization->id,
            format: 'csv',
        );

        $response = app(ExportAuditLogAction::class)->execute($params);

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertSame('text/csv', $response->headers->get('Content-Type'));
    }

    public function test_returns_streamed_response_for_json(): void
    {
        $this->actingAs($this->user);

        AuditLog::factory()->count(2)->create(['organization_id' => $this->organization->id]);

        $params = new AuditLogExportParams(
            organizationId: $this->organization->id,
            format: 'json',
        );

        $response = app(ExportAuditLogAction::class)->execute($params);

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertSame('application/json', $response->headers->get('Content-Type'));
    }

    public function test_csv_export_contains_audit_log_rows(): void
    {
        $this->actingAs($this->user);

        AuditLog::factory()->create([
            'organization_id' => $this->organization->id,
            'event' => 'agent.deployed',
            'description' => 'Test audit entry',
        ]);

        $params = new AuditLogExportParams(
            organizationId: $this->organization->id,
            format: 'csv',
        );

        $response = app(ExportAuditLogAction::class)->execute($params);

        ob_start();
        $response->sendContent();
        $output = ob_get_clean();

        $this->assertStringContainsString('agent.deployed', $output);
        $this->assertStringContainsString('Test audit entry', $output);
    }

    public function test_export_scoped_to_own_organization(): void
    {
        $this->actingAs($this->user);

        $otherOrg = Organization::factory()->create();
        AuditLog::factory()->create([
            'organization_id' => $otherOrg->id,
            'event' => 'other.org.event',
        ]);
        AuditLog::factory()->create([
            'organization_id' => $this->organization->id,
            'event' => 'own.org.event',
        ]);

        $params = new AuditLogExportParams(
            organizationId: $this->organization->id,
            format: 'csv',
        );

        $response = app(ExportAuditLogAction::class)->execute($params);

        ob_start();
        $response->sendContent();
        $output = ob_get_clean();

        $this->assertStringContainsString('own.org.event', $output);
        $this->assertStringNotContainsString('other.org.event', $output);
    }

    public function test_filter_by_event_category(): void
    {
        $this->actingAs($this->user);

        AuditLog::factory()->create([
            'organization_id' => $this->organization->id,
            'event' => 'agent.deployed',
            'event_category' => 'agent_action',
        ]);
        AuditLog::factory()->create([
            'organization_id' => $this->organization->id,
            'event' => 'user.login',
            'event_category' => 'user_action',
        ]);

        $params = new AuditLogExportParams(
            organizationId: $this->organization->id,
            format: 'csv',
            eventCategory: 'agent_action',
        );

        $response = app(ExportAuditLogAction::class)->execute($params);

        ob_start();
        $response->sendContent();
        $output = ob_get_clean();

        $this->assertStringContainsString('agent.deployed', $output);
        $this->assertStringNotContainsString('user.login', $output);
    }
}
