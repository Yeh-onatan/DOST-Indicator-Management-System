<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * AuditLog
 *
 * Represents a single immutable audit entry.
 *
 * SOC 2 & ISO 27001: Comprehensive audit trail requirement
 *
 * Attributes
 * - actor_user_id: nullable FK to users.id, the user who performed the action.
 * - action: string label like "create", "update", "delete", "login", "logout".
 * - entity_type: affected model name (e.g., Objective, ReportingWindow).
 * - entity_id: affected record identifier as a string (ID/UUID/etc.).
 * - changes: array cast. Common shapes:
 *     [ 'diff' => [
 *         'field' => [ 'before' => mixed|null, 'after' => mixed|null ],
 *         ...
 *       ]
 *     ]
 *     or
 *     [ 'deleted' => <model as array> ]
 * - ip_address: client IP address for security monitoring
 * - user_agent: client browser/user agent for forensic analysis
 */
class AuditLog extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'actor_user_id',
        'action',
        'entity_type',
        'entity_id',
        'related_entity_type',
        'related_entity_id',
        'changes',
        'description',
        'batch_id',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        // Cast JSON payload to array so code and Blade can access with [] syntax
        'changes' => 'array',
        'created_at' => 'datetime',
    ];

    public $timestamps = false;

    /**
     * User who performed the action (nullable for system or missing users).
     */
    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    /**
     * Scope to filter by action type
     */
    public function scopeWithAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope to filter by entity
     */
    public function scopeForEntity($query, string $entityType, $entityId)
    {
        return $query->where('entity_type', $entityType)
            ->where('entity_id', $entityId);
    }

    /**
     * Scope to get recent logs
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope to filter by batch ID
     */
    public function scopeWithBatchId($query, string $batchId)
    {
        return $query->where('batch_id', $batchId);
    }

    /**
     * Scope to filter by date range
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }
        return $query;
    }

    /**
     * Scope to filter by actor
     */
    public function scopeByActor($query, $actorId)
    {
        return $query->where('actor_user_id', $actorId);
    }

    /**
     * Scope to filter by related entity
     */
    public function scopeForRelatedEntity($query, string $entityType, $entityId)
    {
        return $query->where('related_entity_type', $entityType)
            ->where('related_entity_id', $entityId);
    }

    /**
     * Scope to search in description
     */
    public function scopeSearchDescription($query, string $search)
    {
        return $query->where('description', 'like', "%{$search}%");
    }

    /**
     * Relationship to the related entity (polymorphic)
     */
    public function relatedEntity()
    {
        return $this->morphTo(null, 'related_entity_type', 'related_entity_id');
    }

    /**
     * Get a human-readable description of the action
     */
    public function getActionDescriptionAttribute(): string
    {
        return match($this->action) {
            'login' => 'Logged in',
            'logout' => 'Logged out',
            'login_failed' => 'Failed login attempt',
            'password_changed' => 'Changed password',
            'password_reset' => 'Reset password',
            'create' => 'Created record',
            'update' => 'Updated record',
            'delete' => 'Deleted record',
            'view' => 'Viewed record',
            'export' => 'Exported data',
            'approve' => 'Approved record',
            'reject' => 'Rejected record',
            'submit' => 'Submitted record',
            'bulk_delete' => 'Bulk deleted records',
            'bulk_update' => 'Bulk updated records',
            'proof_upload' => 'Uploaded proof document',
            'proof_delete' => 'Deleted proof document',
            'workflow_transition' => 'Changed workflow status',
            'settings_change' => 'Changed settings',
            'role_change' => 'Changed user role',
            'permission_grant' => 'Granted permission',
            'permission_revoke' => 'Revoked permission',
            'assignment_create' => 'Created assignment',
            'assignment_delete' => 'Deleted assignment',
            default => $this->action,
        };
    }

    /**
     * Get the icon class for the action type (UI display)
     */
    public function getActionIconAttribute(): string
    {
        return match($this->action) {
            'create', 'assignment_create' => 'plus-circle',
            'update', 'bulk_update', 'settings_change' => 'pencil',
            'delete', 'bulk_delete', 'proof_delete', 'assignment_delete' => 'trash',
            'approve' => 'check-circle',
            'reject' => 'x-circle',
            'submit' => 'arrow-up-circle',
            'proof_upload' => 'upload',
            'workflow_transition' => 'arrow-right-circle',
            'role_change', 'permission_grant', 'permission_revoke' => 'shield',
            'login' => 'log-in',
            'logout' => 'log-out',
            'login_failed' => 'alert-triangle',
            'export' => 'download',
            'view' => 'eye',
            default => 'circle',
        };
    }

    /**
     * Get the color class for the action type (UI display)
     */
    public function getActionColorAttribute(): string
    {
        return match($this->action) {
            'create', 'assignment_create', 'proof_upload', 'approve' => 'success',
            'update', 'bulk_update', 'workflow_transition', 'submit', 'login' => 'primary',
            'delete', 'bulk_delete', 'proof_delete', 'assignment_delete', 'logout' => 'danger',
            'reject', 'login_failed', 'permission_revoke' => 'warning',
            'settings_change', 'role_change', 'permission_grant' => 'info',
            'export', 'view' => 'secondary',
            default => 'secondary',
        };
    }
}
