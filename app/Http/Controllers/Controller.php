<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

abstract class Controller
{
    /**
     * Get the currently authenticated user.
     * Shortcut to avoid repeating Auth::user() in every controller.
     */
    protected function user()
    {
        return Auth::user();
    }

    /**
     * Check if current user is authenticated.
     */
    protected function isAuthenticated(): bool
    {
        return Auth::check();
    }

    /**
     * Check if current user has a specific role.
     */
    protected function hasRole(string $role): bool
    {
        return $this->user()?->role === $role;
    }

    /**
     * Check if current user is administrator or super admin.
     */
    protected function isAdmin(): bool
    {
        $user = $this->user();
        return $user && in_array($user->role, [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN]);
    }

    /**
     * Check if current user is super admin only.
     */
    protected function isSuperAdmin(): bool
    {
        return $this->user()?->role === 'super_admin';
    }

    /**
     * Log controller action for audit trail.
     */
    protected function logAction(string $action, string $entityType = null, $entityId = null, array $changes = null): void
    {
        Log::info('Controller action', [
            'action' => $action,
            'user_id' => $this->user()?->id,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'changes' => $changes,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Return a standardized JSON response for API endpoints.
     */
    protected function success($data = null, string $message = 'Success', int $code = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    /**
     * Return a standardized JSON error response.
     */
    protected function error(string $message = 'Error', $errors = null, int $code = 400)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $code);
    }
}
    