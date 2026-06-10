<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ConsentRequiredMiddleware
 *
 * Enforces GDPR/POPIA consent before allowing access to authenticated routes.
 *
 * Checks that the authenticated user has accepted the platform terms of service
 * (keyed as 'platform_terms' in the consent_records JSON column).
 *
 * Users who have not given consent are redirected to /consent
 * (or to /consent/api-required for JSON requests).
 *
 * Exemptions:
 *  - Unauthenticated requests (let the auth middleware handle those)
 *  - The /consent route itself (prevents infinite redirect)
 *  - Logout route
 */
class ConsentRequiredMiddleware
{
    /**
     * Consent key that must be present and accepted in consent_records.
     */
    private const REQUIRED_CONSENT = 'platform_terms';

    public function handle(Request $request, Closure $next): Response
    {
        // Skip for unauthenticated users — auth middleware handles that
        if (! $request->user()) {
            return $next($request);
        }

        // Skip for the consent page itself, logout, and Jetstream profile routes
        if ($this->isExemptRoute($request)) {
            return $next($request);
        }

        // Check if user has accepted platform terms
        if (! $this->hasConsented($request)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Platform terms acceptance required before proceeding.',
                    'consent_url' => url('/consent'),
                ], Response::HTTP_FORBIDDEN);
            }

            return redirect()->route('consent.show')
                ->with('warning', 'Please accept the platform terms of service to continue.');
        }

        return $next($request);
    }

    /**
     * Check if the authenticated user has given the required consent.
     */
    private function hasConsented(Request $request): bool
    {
        $user = $request->user();
        $records = $user->consent_records ?? [];

        return isset($records[self::REQUIRED_CONSENT])
            && ! empty($records[self::REQUIRED_CONSENT]['accepted_at']);
    }

    /**
     * Routes exempt from consent checking.
     */
    private function isExemptRoute(Request $request): bool
    {
        $exemptPatterns = [
            'consent*',
            'logout',
            'user/profile-information',
            'user/profile',
            'user/password',
            'user/two-factor*',
            'user/confirmed*',
            'livewire/*',
        ];

        foreach ($exemptPatterns as $pattern) {
            if ($request->routeIs($pattern) || $request->is($pattern)) {
                return true;
            }
        }

        return false;
    }
}
