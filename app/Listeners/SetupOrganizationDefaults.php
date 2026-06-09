<?php

namespace App\Listeners;

use App\Events\OrganizationCreated;
use App\Mail\WelcomeEmail;
use App\Services\Governance\AuditService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SetupOrganizationDefaults implements ShouldQueue
{
    public string $queue = 'default';

    public function handle(OrganizationCreated $event): void
    {
        $organization = $event->organization;
        $owner = $organization->owner;

        if (! $owner) {
            return;
        }

        // Send welcome email to the organization owner
        Mail::to($owner->email)->send(new WelcomeEmail($owner, $organization));

        // Log the creation for audit trail
        app(AuditService::class)->logUserAction(
            event: 'organization.created',
            description: "Organization '{$organization->name}' created by user #{$owner->id}",
            subject: $organization,
        );
    }
}
