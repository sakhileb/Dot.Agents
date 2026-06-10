<?php

namespace App\Listeners;

use App\Events\OrganizationCreated;
use App\Mail\WelcomeEmail;
use App\Services\Governance\AuditService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

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

        // Rate-limit welcome emails: max 5 per email address per hour (prevents
        // registration-flood email abuse — a user cycling addresses to spam admins).
        $limiterKey = 'welcome_email:'.sha1(strtolower($owner->email));
        if (RateLimiter::tooManyAttempts($limiterKey, maxAttempts: 5)) {
            Log::warning('[SetupOrganizationDefaults] Welcome email rate-limited', [
                'email' => $owner->email,
                'organization_id' => $organization->id,
            ]);
        } else {
            RateLimiter::hit($limiterKey, decaySeconds: 3600);
            Mail::to($owner->email)->queue(new WelcomeEmail($owner, $organization));
        }

        // Log the creation for audit trail
        app(AuditService::class)->logUserAction(
            event: 'organization.created',
            description: "Organization '{$organization->name}' created by user #{$owner->id}",
            subject: $organization,
        );
    }
}
