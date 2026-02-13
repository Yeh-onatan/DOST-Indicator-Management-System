<?php

namespace App\Actions\Fortify;

use App\Models\PasswordHistory;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

class ResetUserPassword implements ResetsUserPasswords
{
    use PasswordValidationRules;

    /**
     * Validate and reset the user's forgotten password.
     *
     * @param  array<string, string>  $input
     */
    public function reset(User $user, array $input): void
    {
        Validator::make($input, [
            'password' => $this->passwordRules(),
        ])->validate();

        // PCI DSS: Check if password was used before (last 4 passwords)
        // Pass the plaintext password - hasUsedPasswordBefore() uses Hash::check() internally
        if ($user->hasUsedPasswordBefore($input['password'])) {
            Validator::make($input, [])->after(function ($validator) {
                $validator->errors()->add(
                    'password',
                    'You cannot reuse your last 4 passwords. Please choose a different password.'
                );
            })->validate();
        }

        // Save old password to history before changing
        if ($user->password) {
            PasswordHistory::create([
                'user_id' => $user->id,
                'password_hash' => $user->password, // Already hashed
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
            'password' => $input['password'],
            'password_changed_at' => now(),
        ])->save();

        // Log password change for audit trail
        AuditService::logPasswordChange();
    }
}
