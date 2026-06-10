<?php

namespace Tests\Feature\Actions;

use App\Actions\Security\ResolveSecurityEventAction;
use App\Models\Organization;
use App\Models\SecurityEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class ResolveSecurityEventActionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Organization $organization;

    private SecurityEvent $event;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->organization = Organization::factory()->create(['owner_id' => $this->user->id]);
        // Set org context so AuditService can resolve organization_id from session
        session(['current_organization_id' => $this->organization->id]);
        $this->event = SecurityEvent::factory()->create([
            'organization_id' => $this->organization->id,
            'status' => 'open',
            'event_type' => 'prompt_injection',
        ]);
    }

    public function test_resolves_security_event_and_persists_status(): void
    {
        $this->actingAs($this->user);
        Gate::before(fn () => true);

        $resolved = app(ResolveSecurityEventAction::class)->execute(
            $this->event->id,
            'Pattern was a false positive; firewall rule updated.'
        );

        $this->assertEquals('resolved', $resolved->status);
        $this->assertDatabaseHas('security_events', [
            'id' => $this->event->id,
            'status' => 'resolved',
        ]);
    }

    public function test_stores_remediation_notes(): void
    {
        $this->actingAs($this->user);
        Gate::before(fn () => true);

        $notes = 'IP range blocked at perimeter firewall.';
        $resolved = app(ResolveSecurityEventAction::class)->execute($this->event->id, $notes);

        $this->assertEquals($notes, $resolved->remediation_notes);
    }

    public function test_creates_audit_log_on_resolution(): void
    {
        $this->actingAs($this->user);
        Gate::before(fn () => true);

        app(ResolveSecurityEventAction::class)->execute($this->event->id, 'Resolved.');

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'security_event.resolved',
        ]);
    }

    public function test_throws_not_found_for_invalid_event_id(): void
    {
        $this->actingAs($this->user);
        Gate::before(fn () => true);

        $this->expectException(ModelNotFoundException::class);
        app(ResolveSecurityEventAction::class)->execute(999999);
    }
}
