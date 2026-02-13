<?php

namespace App\Actions\Fortify;

use Illuminate\Validation\Rules\Password;

trait PasswordValidationRules
{
    /**
     * Get the validation rules used to validate passwords.
     *
     * PCI DSS Requirement: Passwords must meet complexity requirements:
     * - Minimum 12 characters (PCI DSS 4.1 recommends 12+)
     * - At least one uppercase letter
     * - At least one lowercase letter
     * - At least one number
     * - At least one special character
     *
     * @return array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>
     */
    protected function passwordRules(): array
    {
        return ['required', 'confirmed', Password::min(12)
            ->letters()      // At least one letter (upper and lower)
            ->mixedCase()    // At least one uppercase and one lowercase
            ->numbers()      // At least one number
            ->symbols()      // At least one special character
            ->uncompromised() // Check if password has been leaked in data breaches
        ];
    }
}
