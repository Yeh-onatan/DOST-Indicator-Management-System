<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Backup Log Model
 *
 * Tracks backup operations for compliance and disaster recovery
 * ISO 27001 & SOC 2: Backup verification requirement
 */
class BackupLog extends Model
{
    // Backup types
    public const TYPE_DATABASE = 'database';
    public const TYPE_FILES = 'files';
    public const TYPE_FULL = 'full';

    // Status values
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';
    public const STATUS_WARNING = 'warning';

    protected $fillable = [
        'backup_type',
        'status',
        'location',
        'file_path',
        'file_size_bytes',
        'duration_seconds',
        'details',
        'checksum',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'details' => 'array',
        'file_size_bytes' => 'integer',
        'duration_seconds' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public $timestamps = false;

    /**
     * Scope to filter by backup type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('backup_type', $type);
    }

    /**
     * Scope to get successful backups
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', self::STATUS_SUCCESS);
    }

    /**
     * Scope to get failed backups
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope to get recent backups
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('started_at', '>=', now()->subDays($days));
    }

    /**
     * Get file size in human-readable format
     */
    public function getHumanFileSizeAttribute(): string
    {
        if (!$this->file_size_bytes) {
            return 'N/A';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($this->file_size_bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Get duration in human-readable format
     */
    public function getHumanDurationAttribute(): string
    {
        if (!$this->duration_seconds) {
            return 'N/A';
        }

        if ($this->duration_seconds < 60) {
            return $this->duration_seconds . 's';
        }

        $minutes = floor($this->duration_seconds / 60);
        $seconds = $this->duration_seconds % 60;

        return "{$minutes}m {$seconds}s";
    }

    /**
     * Check if backup is recent (within 24 hours)
     */
    public function isRecent(): bool
    {
        return $this->started_at && $this->started_at->greaterThan(now()->subHours(24));
    }

    /**
     * Get success rate for backup type
     */
    public static function getSuccessRate(string $type, int $days = 30): float
    {
        $total = self::ofType($type)->recent($days)->count();
        $successful = self::ofType($type)->recent($days)->successful()->count();

        if ($total === 0) {
            return 0;
        }

        return round(($successful / $total) * 100, 2);
    }
}
