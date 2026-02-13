<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Security Incident Model
 *
 * Tracks security incidents for incident response and compliance
 * SOC 2 & ISO 27001: Incident management requirement
 */
class SecurityIncident extends Model
{
    // Incident types
    public const TYPE_BRUTE_FORCE = 'brute_force';
    public const TYPE_SQL_INJECTION = 'sql_injection';
    public const TYPE_XSS = 'xss';
    public const TYPE_CSRF = 'csrf';
    public const TYPE_UNAUTHORIZED_ACCESS = 'unauthorized_access';
    public const TYPE_PRIVILEGE_ESCALATION = 'privilege_escalation';
    public const TYPE_DATA_EXFILTRATION = 'data_exfiltration';
    public const TYPE_MALWARE = 'malware';
    public const TYPE_PASSWORD_ATTACK = 'password_attack';
    public const TYPE_RATE_LIMIT_EXCEEDED = 'rate_limit_exceeded';
    public const TYPE_SUSPICIOUS_ACTIVITY = 'suspicious_activity';
    public const TYPE_PASSWORD_EXPIRY = 'password_expiry_issue';
    public const TYPE_VALIDATION_FAILURE = 'validation_failure';

    // Severity levels
    public const SEVERITY_CRITICAL = 'critical';
    public const SEVERITY_HIGH = 'high';
    public const SEVERITY_MEDIUM = 'medium';
    public const SEVERITY_LOW = 'low';

    // Incident status
    public const STATUS_OPEN = 'open';
    public const STATUS_INVESTIGATING = 'investigating';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_FALSE_POSITIVE = 'false_positive';

    protected $fillable = [
        'incident_id',
        'severity',
        'type',
        'status',
        'title',
        'description',
        'details',
        'affected_user_id',
        'reported_by',
        'assigned_to',
        'detected_at',
        'resolved_at',
        'resolution_notes',
    ];

    protected $casts = [
        'details' => 'array',
        'detected_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    /**
     * Generate a unique incident ID
     */
    public static function generateIncidentId(): string
    {
        $prefix = 'INC';
        $timestamp = now()->format('Ymd');
        $random = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));

        return "{$prefix}-{$timestamp}-{$random}";
    }

    /**
     * User affected by this incident
     */
    public function affectedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'affected_user_id');
    }

    /**
     * User who reported this incident
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    /**
     * User assigned to resolve this incident
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Scope to filter by severity
     */
    public function scopeWithSeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Scope to filter by status
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get open incidents
     */
    public function scopeOpen($query)
    {
        return $query->whereIn('status', [self::STATUS_OPEN, self::STATUS_INVESTIGATING]);
    }

    /**
     * Scope to get critical/high incidents
     */
    public function scopeCritical($query)
    {
        return $query->whereIn('severity', [self::SEVERITY_CRITICAL, self::SEVERITY_HIGH]);
    }

    /**
     * Scope to get recent incidents
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('detected_at', '>=', now()->subDays($days));
    }

    /**
     * Check if incident is resolved
     */
    public function isResolved(): bool
    {
        return in_array($this->status, [self::STATUS_RESOLVED, self::STATUS_CLOSED, self::STATUS_FALSE_POSITIVE]);
    }

    /**
     * Resolve the incident
     */
    public function resolve(string $notes = null): bool
    {
        return $this->update([
            'status' => self::STATUS_RESOLVED,
            'resolved_at' => now(),
            'resolution_notes' => $notes,
        ]);
    }

    /**
     * Close the incident
     */
    public function close(string $notes = null): bool
    {
        return $this->update([
            'status' => self::STATUS_CLOSED,
            'resolved_at' => $this->resolved_at ?? now(),
            'resolution_notes' => $notes,
        ]);
    }

    /**
     * Mark as false positive
     */
    public function markAsFalsePositive(string $notes = null): bool
    {
        return $this->update([
            'status' => self::STATUS_FALSE_POSITIVE,
            'resolved_at' => now(),
            'resolution_notes' => $notes,
        ]);
    }

    /**
     * Get severity badge color
     */
    public function getSeverityColorAttribute(): string
    {
        return match($this->severity) {
            self::SEVERITY_CRITICAL => 'red',
            self::SEVERITY_HIGH => 'orange',
            self::SEVERITY_MEDIUM => 'yellow',
            self::SEVERITY_LOW => 'green',
            default => 'gray',
        };
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_OPEN => 'red',
            self::STATUS_INVESTIGATING => 'yellow',
            self::STATUS_RESOLVED => 'green',
            self::STATUS_CLOSED => 'gray',
            self::STATUS_FALSE_POSITIVE => 'blue',
            default => 'gray',
        };
    }
}
