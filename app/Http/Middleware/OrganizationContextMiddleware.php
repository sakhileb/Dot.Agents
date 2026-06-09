<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OrganizationContextMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            // If not set yet, derive from the user's primary organization
            if (! session()->has('current_organization_id')) {
                $org = $request->user()->currentOrganization();
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
}
