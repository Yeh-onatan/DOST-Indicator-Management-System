<?php

namespace App\Http\Controllers;

use App\Models\PasswordHistory;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/**
 * Password Security Controller
 *
 * Handles password changes for expired passwords and security requirements
 * PCI DSS Requirement: Force password change when password expires
 */
class PasswordSecurityController extends Controller
{
    /**
     * Show the password change form
     */
    public function showChangeForm()
    {
        $user = Auth::user();

        // Check if password is actually expired or was recently changed
        $isExpired = $user->isPasswordExpired();
        $daysUntilExpiry = $user->passwordExpiresInDays();

        return view('auth.passwords.change-security', [
            'isExpired' => $isExpired,
            'daysUntilExpiry' => $daysUntilExpiry,
        ]);
    }

    /**
     * Handle the password change request
     */
    public function change(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(12)
                ->mixedCase()
                ->numbers()
                ->symbols()
                ->uncompromised()
            ],
        ], [
            'current_password.current_password' => 'The current password is incorrect.',
            'password.required' => 'The new password is required.',
            'password.confirmed' => 'The password confirmation does not match.',
        ]);

        $user = Auth::user();

        // PCI DSS: Check if password was used before (last 4 passwords)
        // Pass the plaintext password - hasUsedPasswordBefore() uses Hash::check() internally
        if ($user->hasUsedPasswordBefore($request->input('password'))) {
            return back()
                ->withInput()
                ->withErrors([
                    'password' => 'You cannot reuse your last 4 passwords. Please choose a different password.',
                ]);
        }

        // Save old password to history before changing
        if ($user->password) {
            PasswordHistory::create([
                'user_id' => $user->id,
                'password_hash' => $user->password,
                'changed_at' => $user->password_changed_at ?? now(),
            ]);
        }

        // Clean up old password history (keep only last 10)
        $user->passwordHistories()
            ->orderByDesc('changed_at')
            ->offset(10)
            ->delete();

        // Update password and track when it was changed
        $user->forceFill([
            'password' => $request->input('password'),
            'password_changed_at' => now(),
        ])->save();

        // Log password change for audit trail
        AuditService::logPasswordChange();

        // Redirect to intended URL or home
        $intendedUrl = session()->pull('url.intended');
        if ($intendedUrl) {
            return redirect($intendedUrl)->with('status', 'Your password has been changed successfully.');
        }

        return redirect()->route('home')
            ->with('status', 'Your password has been changed successfully.');
    }
}
