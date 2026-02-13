<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsureOUSEC Middleware
 *
 * Restricts access to OUSEC dashboard routes.
 * Allows: OUSEC-STS, OUSEC-RD, OUSEC-RO, Administrators, and SuperAdmins.
 */
class EnsureOUSEC
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || (! $user->isOUSEC() && ! $user->isAdministrator() && ! $user->isSuperAdmin())) {
            abort(403, 'Access restricted to OUSEC personnel.');
        }

        return $next($request);
    }
}
