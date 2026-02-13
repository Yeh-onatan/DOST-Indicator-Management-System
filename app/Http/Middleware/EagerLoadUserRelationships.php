<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Eager Load User Relationships Middleware
 *
 * Eager loads critical user relationships to prevent lazy loading violations
 * when preventLazyLoading is enabled in development.
 *
 * Relationships loaded for authenticated users:
 * - office.region: User's office and its region
 * - agency: User's assigned agency
 * - region: User's assigned region
 */
class EagerLoadUserRelationships
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            // Eager load relationships if not already loaded
            // office.region: Load office and its region for office scope checks
            $user->loadMissing(['office.region', 'agency', 'region']);
        }

        return $next($request);
    }
}

