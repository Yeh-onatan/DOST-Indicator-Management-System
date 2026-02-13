<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Illuminate\Support\Facades\Auth;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        $user = Auth::user();

        // Check user role and determine the appropriate redirect path
        if ($user->isSuperAdmin() || $user->isAdministrator()) {
            // Admin users go to the main admin projects list or dashboard
            return redirect()->intended('/admin/projects');
        } 

        if (
            $user->isProponent() ||
            $user->isHeadOfficer() ||
            $user->isRegionalOffice() ||
            $user->isPsto()
        ) {
            // Proponents go directly to their project entry list
            return redirect()->intended('/proponent/projects');
        }

        // Default fallback if a role is somehow missed (e.g., if we kept the old /dashboard)
        return redirect()->intended(config('fortify.home'));
    }
}
