<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Check Password Expiry Middleware
 *
 * PCI DSS Requirement: Force password change when password expires
 * Redirects users to password change screen if their password has expired
 */
class CheckPasswordExpiry
{
    /**
     * Handle an incoming request.
     *
     * Allows access to password change routes and excludes guest users.
     * For authenticated users with expired passwords, redirects to change password.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip check for guest users
        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();

        // Routes that are allowed even with expired password
        $allowedRoutes = [
            'password.change',
            'password.update',
            'logout',
            'verification.notice',
            'verification.verify',
            'verification.send',
        ];

        // Check if the current route is allowed
        $routeName = $request->route()?->getName();
        if (in_array($routeName, $allowedRoutes)) {
            // Special handling for logout route - only allow POST
            if ($routeName === 'logout' && !$request->isMethod('post')) {
                // Redirect to home if trying to access logout via GET
                return redirect()->home();
            }
            return $next($request);
        }

        // Check if password is expired
        if ($user && $user->isPasswordExpired()) {
            // Store the intended URL for after password change
            session()->put('url.intended', $request->fullUrl());

            return redirect()->route('password.change')
                ->with('warning', 'Your password has expired. Please change your password to continue.');
        }

        return $next($request);
    }
}
