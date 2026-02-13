<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SuperAdminController extends Controller
{
    /**
     * Impersonate a user
     */
    public function impersonate(Request $request, $userId)
    {
        $superAdmin = Auth::user();

        // Only SuperAdmin can impersonate
        if (!$superAdmin || !$superAdmin->isSuperAdmin()) {
            abort(403, 'Unauthorized');
        }

        $targetUser = User::findOrFail($userId);

        // Prevent impersonating yourself
        if ($targetUser->id === $superAdmin->id) {
            return redirect()->back()->with('error', 'Cannot impersonate yourself.');
        }

        // Store original user ID in session
        session()->put('impersonator_id', $superAdmin->id);
        session()->put('impersonator_name', $superAdmin->username);

        // Login as target user
        Auth::login($targetUser);

        Log::warning('SuperAdmin impersonated user', [
            'superadmin_id' => $superAdmin->id,
            'target_user_id' => $targetUser->id,
            'ip' => $request->ip(),
        ]);

        return redirect()->route('dashboard')->with('info', "Impersonating {$targetUser->username}");
    }

    /**
     * Exit impersonation mode
     */
    public function exitImpersonation(Request $request)
    {
        $impersonatorId = session()->pull('impersonator_id');
        $impersonatorName = session()->pull('impersonator_name');

        if (!$impersonatorId) {
            return redirect()->route('dashboard');
        }

        $impersonator = User::findOrFail($impersonatorId);

        // Login back as original user
        Auth::login($impersonator);

        Log::info('SuperAdmin exited impersonation', [
            'superadmin_id' => $impersonator->id,
            'ip' => $request->ip(),
        ]);

        return redirect()->route('dashboard')->with('info', "Exited impersonation mode. Welcome back, {$impersonatorName}!");
    }

    /**
     * Check if user is locked (middleware helper)
     */
    public static function checkLockedStatus($user)
    {
        return $user && $user->is_locked;
    }
}
