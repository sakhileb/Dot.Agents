<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    /**
     * Enterprise security headers applied to every response.
     * Addresses EPRC-01 findings: CSP, HSTS, X-Frame-Options, Referrer-Policy, Permissions-Policy.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Generate nonce BEFORE the view renders so Vite can inject it into its
        // generated <script> tags (module-preload polyfill, dynamic imports, etc.).
        $nonce = $this->nonce();
        Vite::useCspNonce($nonce);

        $response = $next($request);

        // Strict Transport Security — force HTTPS for 1 year including subdomains
        $response->headers->set(
            'Strict-Transport-Security',
            'max-age=31536000; includeSubDomains; preload'
        );

        // Content Security Policy
        // - 'unsafe-eval' is required by Alpine.js v3 (uses new Function() to evaluate expressions)
        // - 'unsafe-inline' in style-src is required by Alpine.js x-show (injects style="display:none")
        // - Livewire 3 uses Alpine internally; removing either will break all wire:click / x-data
        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$nonce}' 'unsafe-eval' https://cdn.jsdelivr.net",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
            "font-src 'self' https://fonts.gstatic.com data:",
            "img-src 'self' data: blob: https:",
            "connect-src 'self' wss: https:",
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            'upgrade-insecure-requests',
        ]);
        $response->headers->set('Content-Security-Policy', $csp);

        // Clickjacking protection
        $response->headers->set('X-Frame-Options', 'DENY');

        // XSS filter for older browsers
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // MIME type sniffing protection
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Referrer policy — limit information leakage
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Permissions policy — disable unnecessary browser features
        $response->headers->set(
            'Permissions-Policy',
            'camera=(), microphone=(), geolocation=(), payment=(), usb=(), interest-cohort=()'
        );

        // Remove server fingerprinting headers
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');

        return $response;
    }

    /**
     * Generate a per-request nonce for inline scripts (CSP nonce).
     */
    protected function nonce(): string
    {
        if (! app()->bound('csp-nonce')) {
            app()->instance('csp-nonce', base64_encode(random_bytes(16)));
        }

        return app('csp-nonce');
    }
}
