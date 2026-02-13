<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Audit Service
 *
 * Centralized service for logging audit events
 * SOC 2 & ISO 27001: Comprehensive audit trail requirement
 */
class AuditService
{
    /**
     * Log an audit event
     *
     * @param string $action The action performed (create, update, delete, login, etc.)
     * @param string|null $entityType The type of entity affected
     * @param mixed|null $entityId The ID of the entity affected
     * @param array|null $changes The changes made (before/after values)
     * @param string|null $description Human-readable description
     * @param string|null $relatedEntityType Related entity type for context
     * @param mixed|null $relatedEntityId Related entity ID for context
     * @param string|null $batchId Batch ID for grouping operations
     * @param Request|null $request The HTTP request (for IP and user agent)
     * @return AuditLog
     */
    public static function log(
        string $action,
        ?string $entityType = null,
        mixed $entityId = null,
        ?array $changes = null,
        ?string $description = null,
        ?string $relatedEntityType = null,
        mixed $relatedEntityId = null,
        ?string $batchId = null,
        ?Request $request = null
    ): AuditLog {
        $request = $request ?? request();

        return AuditLog::create([
            'actor_user_id' => Auth::id(),
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId ? (string) $entityId : null,
            'related_entity_type' => $relatedEntityType,
            'related_entity_id' => $relatedEntityId ? (string) $relatedEntityId : null,
            'changes' => $changes,
            'description' => $description,
            'batch_id' => $batchId,
            'ip_address' => self::getClientIp($request),
            'user_agent' => $request?->userAgent(),
            'created_at' => now(),
        ]);
    }

    /**
     * Log a successful login
     */
    public static function logLogin(?Request $request = null): AuditLog
    {
        return self::log('login', null, null, null, $request);
    }

    /**
     * Log a failed login attempt
     */
    public static function logFailedLogin(?Request $request = null): AuditLog
    {
        $request = $request ?? request();

        return AuditLog::create([
            'actor_user_id' => null, // No user since login failed
            'action' => 'login_failed',
            'entity_type' => null,
            'entity_id' => null,
            'changes' => ['username' => $request?->input('username')],
            'ip_address' => self::getClientIp($request),
            'user_agent' => $request?->userAgent(),
            'created_at' => now(),
        ]);
    }

    /**
     * Log a logout
     */
    public static function logLogout(?Request $request = null): AuditLog
    {
        return self::log('logout', null, null, null, $request);
    }

    /**
     * Log a password change
     */
    public static function logPasswordChange(?Request $request = null): AuditLog
    {
        return self::log('password_changed', 'User', Auth::id(), null, $request);
    }

    /**
     * Log a create action
     */
    public static function logCreate(string $entityType, mixed $entityId, ?array $data = null, ?Request $request = null): AuditLog
    {
        return self::log('create', $entityType, $entityId, ['created' => $data], $request);
    }

    /**
     * Log an update action with diff
     */
    public static function logUpdate(string $entityType, mixed $entityId, ?array $oldValues = null, ?array $newValues = null, ?Request $request = null): AuditLog
    {
        $changes = null;
        if ($oldValues !== null || $newValues !== null) {
            $diff = self::calculateDiff($oldValues ?? [], $newValues ?? []);
            $changes = ['diff' => $diff];
        }

        return self::log('update', $entityType, $entityId, $changes, $request);
    }

    /**
     * Log a delete action
     */
    public static function logDelete(string $entityType, mixed $entityId, ?array $deletedData = null, ?Request $request = null): AuditLog
    {
        return self::log('delete', $entityType, $entityId, ['deleted' => $deletedData], $request);
    }

    /**
     * Log an export action
     */
    public static function logExport(string $entityType, ?array $filters = null, ?Request $request = null): AuditLog
    {
        return self::log('export', $entityType, null, ['filters' => $filters], $request);
    }

    /**
     * Log an approval action
     */
    public static function logApprove(string $entityType, mixed $entityId, ?Request $request = null): AuditLog
    {
        return self::log('approve', $entityType, $entityId, null, $request);
    }

    /**
     * Log a rejection action
     */
    public static function logReject(string $entityType, mixed $entityId, ?string $reason = null, ?Request $request = null): AuditLog
    {
        return self::log('reject', $entityType, $entityId, ['reason' => $reason], $request);
    }

    /**
     * Calculate the difference between two arrays
     */
    protected static function calculateDiff(array $old, array $new): array
    {
        $diff = [];

        foreach ($old as $key => $value) {
            if (!array_key_exists($key, $new)) {
                $diff[$key] = ['before' => $value, 'after' => null];
            } elseif ($new[$key] != $value) {
                $diff[$key] = ['before' => $value, 'after' => $new[$key]];
            }
        }

        foreach ($new as $key => $value) {
            if (!array_key_exists($key, $old)) {
                $diff[$key] = ['before' => null, 'after' => $value];
            }
        }

        return $diff;
    }

    /**
     * Get the client IP address, handling proxies
     */
    protected static function getClientIp(?Request $request): ?string
    {
        if (!$request) {
            return null;
        }

        // Check for forwarded IP (behind proxy/load balancer)
        $forwarded = $request->header('X-Forwarded-For');
        if ($forwarded) {
            // Get the first IP from the comma-separated list
            return explode(',', $forwarded)[0];
        }

        return $request->ip();
    }

    /**
     * Generate a unique batch ID for bulk operations
     */
    public static function generateBatchId(): string
    {
        return (string) Str::uuid();
    }

    /**
     * Log a bulk operation
     *
     * @param string $action The bulk action (bulk_delete, bulk_update, etc.)
     * @param string $entityType The type of entity affected
     * @param array $entityIds Array of entity IDs affected
     * @param string|null $description Human-readable description
     * @param array|null $changes Changes made (for bulk updates)
     * @param Request|null $request The HTTP request
     * @return array Array of AuditLog entries created
     */
    public static function logBulkOperation(
        string $action,
        string $entityType,
        array $entityIds,
        ?string $description = null,
        ?array $changes = null,
        ?Request $request = null
    ): array {
        $batchId = self::generateBatchId();
        $logs = [];

        foreach ($entityIds as $entityId) {
            $logs[] = self::log(
                $action,
                $entityType,
                $entityId,
                $changes,
                $description ?? "Bulk {$action} on {$entityType} #{$entityId}",
                null,
                null,
                $batchId,
                $request
            );
        }

        // Also log a summary entry
        self::log(
            $action,
            $entityType,
            null,
            ['count' => count($entityIds), 'entity_ids' => $entityIds],
            $description ?? "Bulk {$action} on " . count($entityIds) . " {$entityType} records",
            null,
            null,
            $batchId,
            $request
        );

        return $logs;
    }

    /**
     * Log a proof upload
     *
     * @param Model $proof The proof model that was uploaded
     * @param string $action The action (proof_upload, proof_delete, etc.)
     * @param Request|null $request The HTTP request
     * @return AuditLog
     */
    public static function logProofUpload(Model $proof, string $action = 'proof_upload', ?Request $request = null): AuditLog
    {
        $changes = [
            'file_name' => $proof->file_name ?? null,
            'file_path' => $proof->file_path ?? null,
            'file_type' => $proof->file_type ?? null,
            'objective_id' => $proof->objective_id ?? null,
        ];

        return self::log(
            $action,
            'Proof',
            $proof->id,
            $changes,
            "Uploaded proof document: " . ($proof->file_name ?? 'Unknown'),
            'Objective',
            $proof->objective_id ?? null,
            null,
            $request
        );
    }

    /**
     * Log a proof deletion
     *
     * @param Model $proof The proof model being deleted
     * @param Request|null $request The HTTP request
     * @return AuditLog
     */
    public static function logProofDelete(Model $proof, ?Request $request = null): AuditLog
    {
        return self::logProofUpload($proof, 'proof_delete', $request);
    }

    /**
     * Log a workflow transition
     *
     * @param Model $objective The objective model
     * @param string $fromStatus The previous status
     * @param string $toStatus The new status
     * @param Request|null $request The HTTP request
     * @return AuditLog
     */
    public static function logWorkflowTransition(
        Model $objective,
        string $fromStatus,
        string $toStatus,
        ?Request $request = null
    ): AuditLog {
        $changes = [
            'from' => $fromStatus,
            'to' => $toStatus,
        ];

        $description = "Workflow transition for Objective #{$objective->id}: {$fromStatus} → {$toStatus}";

        return self::log(
            'workflow_transition',
            'Objective',
            $objective->id,
            $changes,
            $description,
            null,
            null,
            null,
            $request
        );
    }

    /**
     * Log a settings change
     *
     * @param string $settingKey The setting key that was changed
     * @param mixed $oldValue The previous value
     * @param mixed $newValue The new value
     * @param string|null $context Additional context (e.g., user ID, agency ID)
     * @param Request|null $request The HTTP request
     * @return AuditLog
     */
    public static function logSettingsChange(
        string $settingKey,
        $oldValue,
        $newValue,
        ?string $context = null,
        ?Request $request = null
    ): AuditLog {
        $changes = [
            'key' => $settingKey,
            'old' => $oldValue,
            'new' => $newValue,
        ];

        $description = "Changed setting '{$settingKey}'";

        if ($context) {
            $description .= " for {$context}";
        }

        return self::log(
            'settings_change',
            'Setting',
            $settingKey,
            $changes,
            $description,
            null,
            null,
            null,
            $request
        );
    }

    /**
     * Log a role change
     *
     * @param Model $user The user model
     * @param string $oldRole The previous role
     * @param string $newRole The new role
     * @param Request|null $request The HTTP request
     * @return AuditLog
     */
    public static function logRoleChange(
        Model $user,
        string $oldRole,
        string $newRole,
        ?Request $request = null
    ): AuditLog {
        $changes = [
            'from' => $oldRole,
            'to' => $newRole,
        ];

        return self::log(
            'role_change',
            'User',
            $user->id,
            $changes,
            "Changed role for user {$user->name} ({$user->email}): {$oldRole} → {$newRole}",
            null,
            null,
            null,
            $request
        );
    }

    /**
     * Log an assignment creation (HO to agency, etc.)
     *
     * @param string $assigneeType The type of entity being assigned (User, Agency)
     * @param mixed $assigneeId The ID of the entity being assigned
     * @param string $assignmentType The type of assignment (head_office, regional_office, etc.)
     * @param Model|null $assignedTo The entity they're assigned to (Agency, Office, etc.)
     * @param Request|null $request The HTTP request
     * @return AuditLog
     */
    public static function logAssignmentCreate(
        string $assigneeType,
        mixed $assigneeId,
        string $assignmentType,
        ?Model $assignedTo = null,
        ?Request $request = null
    ): AuditLog {
        $description = "Assigned {$assigneeType} #{$assigneeId} as {$assignmentType}";

        if ($assignedTo) {
            $description .= " to {$assignedTo->getMorphClass()} #{$assignedTo->id}";
        }

        return self::log(
            'assignment_create',
            $assigneeType,
            $assigneeId,
            ['assignment_type' => $assignmentType],
            $description,
            $assignedTo?->getMorphClass(),
            $assignedTo?->id,
            null,
            $request
        );
    }

    /**
     * Log an assignment deletion
     *
     * @param string $assigneeType The type of entity that was assigned
     * @param mixed $assigneeId The ID of the entity that was assigned
     * @param string $assignmentType The type of assignment that was removed
     * @param Model|null $wasAssignedTo The entity they were assigned to
     * @param Request|null $request The HTTP request
     * @return AuditLog
     */
    public static function logAssignmentDelete(
        string $assigneeType,
        mixed $assigneeId,
        string $assignmentType,
        ?Model $wasAssignedTo = null,
        ?Request $request = null
    ): AuditLog {
        $description = "Removed {$assigneeType} #{$assigneeId} from {$assignmentType}";

        if ($wasAssignedTo) {
            $description .= " at {$wasAssignedTo->getMorphClass()} #{$wasAssignedTo->id}";
        }

        return self::log(
            'assignment_delete',
            $assigneeType,
            $assigneeId,
            ['assignment_type' => $assignmentType],
            $description,
            $wasAssignedTo?->getMorphClass(),
            $wasAssignedTo?->id,
            null,
            $request
        );
    }

    /**
     * Get entity history for a specific entity
     *
     * @param string $entityType The type of entity
     * @param mixed $entityId The ID of the entity
     * @param int $limit Maximum number of records to return
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getEntityHistory(string $entityType, mixed $entityId, int $limit = 100)
    {
        return AuditLog::forEntity($entityType, $entityId)
            ->with('actor')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get audit logs with advanced filtering
     *
     * @param array $filters Filter criteria
     * @param int $perPage Items per page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public static function getFilteredLogs(array $filters = [], int $perPage = 50)
    {
        $query = AuditLog::with('actor');

        // Filter by actor
        if (!empty($filters['actor_user_id'])) {
            $query->byActor($filters['actor_user_id']);
        }

        // Filter by entity type and/or ID
        if (!empty($filters['entity_type'])) {
            $query->where('entity_type', $filters['entity_type']);
            if (!empty($filters['entity_id'])) {
                $query->where('entity_id', $filters['entity_id']);
            }
        }

        // Filter by action
        if (!empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        // Filter by date range
        if (!empty($filters['start_date']) || !empty($filters['end_date'])) {
            $query->inDateRange(
                $filters['start_date'] ?? null,
                $filters['end_date'] ?? null
            );
        }

        // Filter by batch ID
        if (!empty($filters['batch_id'])) {
            $query->withBatchId($filters['batch_id']);
        }

        // Search in description
        if (!empty($filters['search'])) {
            $query->searchDescription($filters['search']);
        }

        // Filter by related entity
        if (!empty($filters['related_entity_type']) && !empty($filters['related_entity_id'])) {
            $query->forRelatedEntity($filters['related_entity_type'], $filters['related_entity_id']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }
}
