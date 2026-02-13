<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable, SoftDeletes;

    // --- ROLE CONSTANTS ---
    public const ROLE_SUPER_ADMIN = 'super_admin';
    public const ROLE_ADMIN = 'administrator';
    public const ROLE_EXECOM = 'execom';
    public const ROLE_HO = 'head_officer';
    public const ROLE_RO = 'ro';
    public const ROLE_PSTO = 'psto';
    public const ROLE_AGENCY = 'agency';
    public const ROLE_PROPONENT = 'proponent'; // Legacy role, use AGENCY instead

    // --- OUSEC ROLE CONSTANTS ---
    public const ROLE_OUSEC_STS = 'ousec_sts';
    public const ROLE_OUSEC_RD = 'ousec_rd';
    public const ROLE_OUSEC_RO = 'ousec_ro';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'username',
        'role',
        'ousec_type',
        'assigned_clusters',
        'region_id',
        'office_id',
        'agency_id',
        'email_verified_at',
        'email_notifications_enabled',
        'last_login_at',
        'is_locked',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password_changed_at' => 'datetime',
            'password' => 'hashed',
            'assigned_clusters' => 'array', // Cast JSON to array
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    // --- Role-Based Helper Methods ---

    /**
     * Check if the user has a specific role.
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    public function isAdministrator(): bool
    {
        return in_array($this->role, ['admin', 'administrator', self::ROLE_ADMIN]);
    }

    public function isExecom(): bool
    {
        return $this->roleEquals(self::ROLE_EXECOM);
    }

    public function isProponent(): bool
    {
        return $this->roleEquals(self::ROLE_PROPONENT);
    }

    protected function roleEquals(string $target): bool
    {
        return strcasecmp($this->role ?? '', $target) === 0;
    }

    public function isHeadOfficer(): bool
    {
        return $this->roleEquals(self::ROLE_HO);
    }

    public function isRegionalOffice(): bool
    {
        return $this->roleEquals(self::ROLE_RO);
    }

    public function isPsto(): bool
    {
        return $this->roleEquals(self::ROLE_PSTO);
    }

    /**
     * Check if user is an Agency account.
     */
    public function isAgency(): bool
    {
        return $this->roleEquals(self::ROLE_AGENCY);
    }

    // Aliases
    /**
     * Check if user can act as Head of Office (role OR assignment-based)
     * This is the main method to use for all HO permission checks
     */
    public function canActAsHeadOfOffice(): bool
    {
        // Check 1: Has the head_officer role
        if ($this->isHeadOfficer()) {
            return true;
        }

        // Check 2: Is assigned as head of any office
        // Use loadMissing to avoid lazy loading violations
        if ($this->office_id) {
            $this->loadMissing('office');
            if ($this->office && $this->office->head_user_id === $this->id) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the office this user is head of (if any)
     */
    public function getHeadOfOffice(): ?Office
    {
        $this->loadMissing('office');
        if ($this->office && $this->office->head_user_id === $this->id) {
            return $this->office;
        }

        return null;
    }

    public function isRO(): bool
    {
        return $this->isRegionalOffice();
    }

    public function isSA(): bool
    {
        return $this->isSuperAdmin();
    }

    // --- OUSEC Role Methods ---

    public function isOUSEC(): bool
    {
        return in_array($this->role, [
            self::ROLE_OUSEC_STS,
            self::ROLE_OUSEC_RD,
            self::ROLE_OUSEC_RO,
        ]);
    }

    public function isOUSECSTS(): bool
    {
        return $this->roleEquals(self::ROLE_OUSEC_STS);
    }

    public function isOUSECRD(): bool
    {
        return $this->roleEquals(self::ROLE_OUSEC_RD);
    }

    public function isOUSEROR(): bool
    {
        return $this->roleEquals(self::ROLE_OUSEC_RO);
    }

    /**
     * Get the OUSEC cluster types this user can review
     * Returns agency cluster codes based on OUSEC role
     */
    public function getOUSECClusters(): array
    {
        return match($this->role) {
            self::ROLE_OUSEC_STS => \App\Constants\AgencyConstants::OUSEC_STS_CLUSTERS,
            self::ROLE_OUSEC_RD => \App\Constants\AgencyConstants::OUSEC_RD_CLUSTERS,
            self::ROLE_OUSEC_RO => [], // Regional (uses different logic)
            default => [],
        };
    }

    /**
     * Check if this OUSEC user can review indicators from a specific agency
     */
    public function canReviewAgency(?DOSTAgency $agency): bool
    {
        if (!$this->isOUSEC() || !$agency) {
            return false;
        }

        // OUSEC-RO can review all regional/PSTO indicators (not agency-based)
        if ($this->isOUSEROR()) {
            return true; // Will check region_id in scope
        }

        // OUSEC-STS and OUSEC-RD check agency cluster
        $allowedClusters = $this->getOUSECClusters();
        return in_array($agency->cluster, $allowedClusters);
    }

    // Relationships
    public function settings()
    {
        return $this->hasOne(UserSetting::class);
    }

    public function region()
    {
        return $this->belongsTo(PhilippineRegion::class, 'region_id');
    }

    public function office()
    {
        return $this->belongsTo(Office::class);
    }

    public function agency()
    {
        return $this->belongsTo(DOSTAgency::class, 'agency_id');
    }

    /**
     * Get all audit logs for this user (actions performed by this user)
     */
    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class, 'actor_user_id');
    }

    // --- RBAC Relationships ---

    /**
     * Get direct permissions assigned to this user (bypassing role).
     */
    public function directPermissions()
    {
        return $this->belongsToMany(Permission::class, 'permission_user');
    }

    /**
     * Get all permissions for this user (via role + direct assignments).
     */
    public function allPermissions()
    {
        if ($this->role) {
            $roleModel = \App\Models\Role::where('name', $this->role)->first();
            if ($roleModel) {
                return $roleModel->permissions;
            }
        }
        return $this->directPermissions;
    }

    // --- Permission Checking Methods ---

    /**
     * Check if user has a specific permission.
     * Super admins have all permissions.
     */
    public function hasPermission(string $permissionName): bool
    {
        // Super admin has all permissions
        if ($this->isSuperAdmin()) {
            return true;
        }

        // Check via role
        if ($this->role) {
            $roleModel = \App\Models\Role::where('name', $this->role)->first();
            if ($roleModel && $roleModel->hasPermission($permissionName)) {
                return true;
            }
        }

        // Check direct permissions
        return $this->directPermissions()->where('name', $permissionName)->exists();
    }

    /**
     * Check if user has any of the given permissions.
     */
    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if user has all of the given permissions.
     */
    public function hasAllPermissions(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($permission)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Check if user has a specific permission by name (alias for hasPermission).
     * Use this method for permission-based access control in new code.
     *
     * @param string $permission
     * @return bool
     */
    public function checkPermission(string $permission): bool
    {
        return $this->hasPermission($permission);
    }

    /**
     * Check if user lacks a specific permission.
     *
     * @param string $permission
     * @return bool
     */
    public function cannotPermission(string $permission): bool
    {
        return !$this->checkPermission($permission);
    }

    // Password Security Relationships
    public function passwordHistories()
    {
        return $this->hasMany(PasswordHistory::class)->orderByDesc('changed_at');
    }

    /**
     * Check if the user's password has expired
     * PCI DSS Requirement: Passwords must expire every 90 days or less
     */
    public function isPasswordExpired(): bool
    {
        if (!$this->password_changed_at) {
            return true; // Password has never been changed
        }

        $expiryDays = $this->password_expiry_days ?? 90;
        $expiryDate = $this->password_changed_at->addDays($expiryDays);

        return now()->greaterThan($expiryDate);
    }

    /**
     * Get the number of days until password expires
     */
    public function passwordExpiresInDays(): ?int
    {
        if (!$this->password_changed_at) {
            return 0;
        }

        $expiryDays = $this->password_expiry_days ?? 90;
        $expiryDate = $this->password_changed_at->addDays($expiryDays);
        $daysRemaining = now()->diffInDays($expiryDate, false);

        return max(0, $daysRemaining);
    }

    /**
     * Check if a new password was previously used
     * PCI DSS Requirement: Prevent reuse of last 4 passwords
     */
    /**
     * Check if a PLAIN TEXT password was previously used.
     *
     * WHY THE OLD CODE WAS BROKEN:
     * hash_equals() compares two strings byte-for-byte. But bcrypt
     * generates a DIFFERENT hash every time for the same password
     * (because of the random salt). So hash_equals() would NEVER
     * match, making password reuse prevention completely broken.
     *
     * FIX: Use Hash::check() which properly verifies a plain-text
     * password against a bcrypt hash.
     *
     * @param string $plainPassword The plain-text password to check (NOT a hash)
     * @param int $historyCount How many previous passwords to check against
     */
    public function hasUsedPasswordBefore(string $plainPassword, int $historyCount = 4): bool
    {
        $previousHashes = $this->passwordHistories()
            ->orderByDesc('changed_at')
            ->limit($historyCount)
            ->pluck('password_hash');

        foreach ($previousHashes as $oldHash) {
            if (\Illuminate\Support\Facades\Hash::check($plainPassword, $oldHash)) {
                return true;
            }
        }

        return false;
    }
}