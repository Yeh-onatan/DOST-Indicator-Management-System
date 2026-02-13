<?php

namespace App\Services;

use App\Models\SecurityIncident;
use App\Models\User;
use App\Notifications\SecurityIncidentNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Incident Service
 *
 * Centralized service for managing security incidents
 * SOC 2 & ISO 27001: Incident detection, response, and notification
 */
class IncidentService
{
    /**
     * Report a security incident
     *
     * @param string $type Incident type (use SecurityIncident::TYPE_* constants)
     * @param string $severity Severity level (critical, high, medium, low)
     * @param string $title Incident title
     * @param string|null $description Detailed description
     * @param array|null $details Additional context (IPs, user agents, etc.)
     * @param int|null $affectedUserId User affected by the incident
     * @return SecurityIncident
     */
    public static function report(
        string $type,
        string $severity,
        string $title,
        ?string $description = null,
        ?array $details = null,
        ?int $affectedUserId = null
    ): SecurityIncident {
        // Determine affected user if not provided
        if ($affectedUserId === null && Auth::check()) {
            $affectedUserId = Auth::id();
        }

        $incident = SecurityIncident::create([
            'incident_id' => SecurityIncident::generateIncidentId(),
            'type' => $type,
            'severity' => $severity,
            'status' => SecurityIncident::STATUS_OPEN,
            'title' => $title,
            'description' => $description ?? $title,
            'details' => $details,
            'affected_user_id' => $affectedUserId,
            'reported_by' => Auth::id(),
            'detected_at' => now(),
        ]);

        // Log to system logs for SIEM integration
        Log::warning('Security Incident Detected', [
            'incident_id' => $incident->incident_id,
            'type' => $incident->type,
            'severity' => $incident->severity,
            'title' => $incident->title,
        ]);

        // Auto-assign based on severity
        self::autoAssign($incident);

        // Send notifications based on severity
        self::sendNotifications($incident);

        return $incident;
    }

    /**
     * Report a brute force attack
     */
    public static function reportBruteForce(string $username, string $ipAddress, int $attemptCount): SecurityIncident
    {
        return self::report(
            SecurityIncident::TYPE_BRUTE_FORCE,
            $attemptCount > 10 ? SecurityIncident::SEVERITY_HIGH : SecurityIncident::SEVERITY_MEDIUM,
            "Brute Force Attack Detected",
            "Multiple failed login attempts detected for username: {$username}",
            [
                'username' => $username,
                'ip_address' => $ipAddress,
                'attempt_count' => $attemptCount,
                'user_agent' => request()?->userAgent(),
            ]
        );
    }

    /**
     * Report a rate limit exceeded event
     */
    public static function reportRateLimitExceeded(string $limiterName, string $ipAddress): SecurityIncident
    {
        return self::report(
            SecurityIncident::TYPE_RATE_LIMIT_EXCEEDED,
            SecurityIncident::SEVERITY_LOW,
            "Rate Limit Exceeded: {$limiterName}",
            "Client exceeded rate limit for {$limiterName}",
            [
                'limiter' => $limiterName,
                'ip_address' => $ipAddress,
                'url' => request()?->fullUrl(),
            ]
        );
    }

    /**
     * Report suspicious validation activity
     */
    public static function reportSuspiciousValidation(string $field, mixed $value, string $reason): SecurityIncident
    {
        return self::report(
            SecurityIncident::TYPE_SUSPICIOUS_ACTIVITY,
            SecurityIncident::SEVERITY_MEDIUM,
            "Suspicious Input Detected",
            "Suspicious input detected in {$field}: {$reason}",
            [
                'field' => $field,
                'value_preview' => is_string($value) ? substr($value, 0, 100) : json_encode($value),
                'reason' => $reason,
                'ip_address' => request()?->ip(),
                'url' => request()?->fullUrl(),
            ],
            Auth::id()
        );
    }

    /**
     * Report unauthorized access attempt
     */
    public static function reportUnauthorizedAccess(string $resource, ?string $reason = null): SecurityIncident
    {
        return self::report(
            SecurityIncident::TYPE_UNAUTHORIZED_ACCESS,
            SecurityIncident::SEVERITY_HIGH,
            "Unauthorized Access Attempt",
            $reason ?? "Attempted unauthorized access to {$resource}",
            [
                'resource' => $resource,
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
                'url' => request()?->fullUrl(),
            ],
            Auth::id()
        );
    }

    /**
     * Report potential SQL injection
     */
    public static function reportSqlInjection(string $field, string $pattern): SecurityIncident
    {
        return self::report(
            SecurityIncident::TYPE_SQL_INJECTION,
            SecurityIncident::SEVERITY_CRITICAL,
            "Potential SQL Injection Detected",
            "SQL injection pattern detected in {$field}",
            [
                'field' => $field,
                'pattern' => substr($pattern, 0, 100),
                'ip_address' => request()?->ip(),
                'url' => request()?->fullUrl(),
            ],
            Auth::id()
        );
    }

    /**
     * Report potential XSS attempt
     */
    public static function reportXss(string $field, string $pattern): SecurityIncident
    {
        return self::report(
            SecurityIncident::TYPE_XSS,
            SecurityIncident::SEVERITY_CRITICAL,
            "Potential XSS Attack Detected",
            "Cross-site scripting pattern detected in {$field}",
            [
                'field' => $field,
                'pattern' => substr($pattern, 0, 100),
                'ip_address' => request()?->ip(),
                'url' => request()?->fullUrl(),
            ],
            Auth::id()
        );
    }

    /**
     * Auto-assign incident based on severity
     */
    protected static function autoAssign(SecurityIncident $incident): void
    {
        // Critical and high incidents go to super admins
        if (in_array($incident->severity, [SecurityIncident::SEVERITY_CRITICAL, SecurityIncident::SEVERITY_HIGH])) {
            $superAdmins = User::where('role', User::ROLE_SUPER_ADMIN)->active()->get();

            if ($superAdmins->isNotEmpty()) {
                // Assign to the first super admin (could implement rotation/round-robin)
                $incident->update(['assigned_to' => $superAdmins->first()->id]);
            }
        }
    }

    /**
     * Send notifications based on severity
     */
    protected static function sendNotifications(SecurityIncident $incident): void
    {
        // Critical incidents: immediate notification to all admins
        if ($incident->severity === SecurityIncident::SEVERITY_CRITICAL) {
            $admins = User::whereIn('role', [User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN])->get();

            foreach ($admins as $admin) {
                $admin->notify(new SecurityIncidentNotification($incident));
            }
        }
        // High incidents: notify super admins
        elseif ($incident->severity === SecurityIncident::SEVERITY_HIGH) {
            $superAdmins = User::where('role', User::ROLE_SUPER_ADMIN)->get();

            foreach ($superAdmins as $admin) {
                $admin->notify(new SecurityIncidentNotification($incident));
            }
        }
    }

    /**
     * Get incident statistics for dashboard
     */
    public static function getStatistics(int $days = 30): array
    {
        $query = SecurityIncident::recent($days);

        return [
            'total' => $query->count(),
            'open' => (clone $query)->open()->count(),
            'critical' => (clone $query)->withSeverity(SecurityIncident::SEVERITY_CRITICAL)->count(),
            'high' => (clone $query)->withSeverity(SecurityIncident::SEVERITY_HIGH)->count(),
            'medium' => (clone $query)->withSeverity(SecurityIncident::SEVERITY_MEDIUM)->count(),
            'low' => (clone $query)->withSeverity(SecurityIncident::SEVERITY_LOW)->count(),
            'resolved' => (clone $query)->withStatus(SecurityIncident::STATUS_RESOLVED)->count(),
        ];
    }
}
