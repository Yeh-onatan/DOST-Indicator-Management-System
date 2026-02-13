<?php

namespace App\Services;

use App\Models\SecurityIncident;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Security Monitor Service
 *
 * Monitors for suspicious activity and anomalies
 * SOC 2 & ISO 27001: Continuous security monitoring requirement
 */
class SecurityMonitorService
{
    /**
     * Track failed login attempts for potential brute force detection
     */
    public static function trackFailedLogin(string $identifier, string $ipAddress): void
    {
        $key = "failed_login:{$identifier}:{$ipAddress}";
        $count = Cache::increment($key, 1);
        Cache::expire($key, 900); // 15 minutes window

        // Report after 5 failed attempts
        if ($count === 5) {
            IncidentService::reportBruteForce($identifier, $ipAddress, $count);
        }

        Log::warning('Failed login attempt', [
            'identifier' => $identifier,
            'ip' => $ipAddress,
            'count' => $count,
        ]);
    }

    /**
     * Clear failed login attempts on successful login
     */
    public static function clearFailedLogins(string $identifier, string $ipAddress): void
    {
        $key = "failed_login:{$identifier}:{$ipAddress}";
        Cache::forget($key);
    }

    /**
     * Detect unusual login patterns (e.g., login from new location)
     */
    public static function detectUnusualLogin(User $user, string $ipAddress, string $userAgent): ?array
    {
        $key = "user_locations:{$user->id}";
        $knownLocations = Cache::get($key, []);

        $isKnownLocation = in_array($ipAddress, $knownLocations);

        // Add to known locations
        if (!$isKnownLocation) {
            $knownLocations[] = $ipAddress;
            // Keep last 10 locations
            if (count($knownLocations) > 10) {
                array_shift($knownLocations);
            }
            Cache::put($key, $knownLocations, now()->addDays(30));
        }

        // Check for suspicious patterns
        $recentLogins = Cache::get("recent_logins:{$user->id}", []);
        $now = now();

        // More than 5 logins in the last minute
        $recentLogins[] = $now->toDateTimeString();
        $recentLogins = array_filter($recentLogins, function ($timestamp) use ($now) {
            return $now->subMinute()->diffInMinutes(\Carbon\Carbon::parse($timestamp), false) <= 1;
        });

        Cache::put("recent_logins:{$user->id}", $recentLogins, 300);

        if (count($recentLogins) > 5) {
            return [
                'type' => 'rapid_logins',
                'message' => 'Multiple rapid login sessions detected',
                'count' => count($recentLogins),
            ];
        }

        // New location alert
        if (!$isKnownLocation && count($knownLocations) > 1) {
            return [
                'type' => 'new_location',
                'message' => 'Login from a new location detected',
                'ip' => $ipAddress,
            ];
        }

        return null;
    }

    /**
     * Monitor for data exfiltration patterns
     */
    public static function monitorDataExport(int $userId, string $entityType, int $recordCount): void
    {
        $key = "exports:{$userId}:{$entityType}:" . now()->format('Ymd');
        $exports = Cache::get($key, []);
        $exports[] = [
            'count' => $recordCount,
            'time' => now()->toDateTimeString(),
        ];

        Cache::put($key, $exports, now()->endOfDay());

        // Check for suspicious export activity
        $totalRecords = array_sum(array_column($exports, 'count'));

        // More than 1000 records exported in a day
        if ($totalRecords > 1000) {
            IncidentService::report(
                SecurityIncident::TYPE_DATA_EXFILTRATION,
                SecurityIncident::SEVERITY_HIGH,
                'Excessive Data Export Detected',
                "User exported {$totalRecords} records of type {$entityType} in a single day",
                [
                    'user_id' => $userId,
                    'entity_type' => $entityType,
                    'total_records' => $totalRecords,
                    'export_count' => count($exports),
                ]
            );
        }
    }

    /**
     * Monitor for privilege escalation attempts
     */
    public static function monitorPrivilegeEscalation(int $userId, string $attemptedRole): bool
    {
        $user = User::find($userId);
        if (!$user) {
            return true; // Block non-existent users
        }

        $allowedRoles = [
            User::ROLE_SUPER_ADMIN => [],
            User::ROLE_ADMIN => [User::ROLE_EXECOM, User::ROLE_HEAD_OFFICER, User::ROLE_RO, User::ROLE_PSTO, User::ROLE_AGENCY],
            User::ROLE_EXECOM => [User::ROLE_HEAD_OFFICER, User::ROLE_RO, User::ROLE_PSTO, User::ROLE_AGENCY],
        ];

        $userRole = $user->role;

        // Check if user has permission to assign this role
        $canAssign = isset($allowedRoles[$userRole]) && in_array($attemptedRole, $allowedRoles[$userRole]);

        if (!$canAssign) {
            IncidentService::report(
                SecurityIncident::TYPE_PRIVILEGE_ESCALATION,
                SecurityIncident::SEVERITY_HIGH,
                'Privilege Escalation Attempt Detected',
                "User {$user->username} attempted to assign role '{$attemptedRole}' without proper authorization",
                [
                    'user_id' => $userId,
                    'current_role' => $userRole,
                    'attempted_role' => $attemptedRole,
                    'ip_address' => request()?->ip(),
                ],
                $userId
            );
        }

        return $canAssign;
    }

    /**
     * Get security metrics for dashboard
     */
    public static function getSecurityMetrics(int $days = 30): array
    {
        return [
            'failed_logins' => self::getFailedLoginCount($days),
            'suspicious_activity' => SecurityIncident::recent($days)->count(),
            'data_exports' => self::getDataExportCount($days),
            'open_incidents' => SecurityIncident::open()->count(),
        ];
    }

    /**
     * Get failed login count
     */
    protected static function getFailedLoginCount(int $days): int
    {
        // This would query audit logs for failed logins
        return \App\Models\AuditLog::withAction('login_failed')
            ->recent($days)
            ->count();
    }

    /**
     * Get data export count
     */
    protected static function getDataExportCount(int $days): int
    {
        return \App\Models\AuditLog::withAction('export')
            ->recent($days)
            ->count();
    }
}
