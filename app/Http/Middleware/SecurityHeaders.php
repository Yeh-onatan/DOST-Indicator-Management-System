<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        if (! $response instanceof Response) {
            $response = response($response ?? '');
        }

        // Prevent clickjacking
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // Prevent MIME type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Enable XSS protection (browser-side)
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Referrer policy
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Content Security Policy (basic - can be enhanced)
        if (app()->environment('local', 'testing')) {
            // Development: Disable CSP to allow Vite HMR to work without issues
            $csp = null; // No CSP in development
        } else {
            // Production: Stricter CSP
            $csp = "default-src 'self'; " .
                   "script-src 'self' 'unsafe-inline' 'unsafe-eval'; " .
                   "style-src 'self' 'unsafe-inline' https://fonts.bunny.net; " .
                   "img-src 'self' data: https:; " .
                   "font-src 'self' data: https://fonts.bunny.net; " .
                   "connect-src 'self';";
        }
        if ($csp) {
            $response->headers->set('Content-Security-Policy', $csp);
        }

        // Permissions policy
        $permissions = "camera=(), microphone=(), geolocation=()";
        $response->headers->set('Permissions-Policy', $permissions);

        // HSTS (HTTP Strict Transport Security) - PCI DSS compliance
        // Only add HSTS if the request is already over HTTPS
        if ($request->secure()) {
            $maxAge = 31536000; // 1 year in seconds (recommended: 6 months to 1 year)
            $includeSubDomains = true; // Apply to all subdomains
            $preload = false; // Set to true and submit to hstspreload.org if desired

            $hsts = "max-age={$maxAge}";
            if ($includeSubDomains) {
                $hsts .= "; includeSubDomains";
            }
            if ($preload) {
                $hsts .= "; preload";
            }

            $response->headers->set('Strict-Transport-Security', $hsts);
        }

        return $response;
    }
}
