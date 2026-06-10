<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class OrganizationContextMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            // If not set yet, derive from the user's primary organization
            if (! session()->has('current_organization_id')) {
                $org = $request->user()->currentOrganization();

                // Auto-create an org for users that registered before this fix
                if (! $org) {
                    $org = $this->createOrganizationForUser($request->user());
                }

                if ($org) {
                    session(['current_organization_id' => $org->id]);
                }
            }

            // Validate that the session org still belongs to this user
            $orgId = session('current_organization_id');
            if ($orgId) {
                $valid = $request->user()
                    ->organizations()
                    ->where('organizations.id', $orgId)
                    ->exists();

                if (! $valid) {
                    session()->forget('current_organization_id');
                }
            }
        }

        return $next($request);
    }

    /**
     * Bootstrap a personal Organization for users that pre-date the
     * automatic org creation on registration.
     */
    private function createOrganizationForUser(User $user): ?Organization
    {
        $baseName = explode(' ', $user->name, 2)[0]."'s Organization";
        $baseSlug = Str::slug($baseName);

        $slug = $baseSlug;
        $count = 1;
        while (Organization::where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$count++;
        }

        $org = Organization::create([
            'name' => $baseName,
            'slug' => $slug,
            'owner_id' => $user->id,
            'plan' => 'starter',
            'status' => 'trial',
            'timezone' => 'UTC',
            'currency' => 'USD',
            'trial_ends_at' => now()->addDays(14),
        ]);

        $org->users()->attach($user->id, [
            'role' => 'owner',
            'is_primary' => true,
            'joined_at' => now(),
        ]);

        return $org;
    }
}
