<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Password History Model
 *
 * Tracks password changes to prevent reuse of previous passwords
 * PCI DSS Requirement: Users must not reuse the last 4 passwords
 */
class PasswordHistory extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'user_id',
        'password_hash',
        'changed_at',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
    ];

    /**
     * The user who owns this password history entry
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if a plain-text password matches this history entry.
     * Uses Hash::check() because bcrypt hashes are non-deterministic.
     */
    public function matchesPassword(string $plainPassword): bool
    {
        return \Illuminate\Support\Facades\Hash::check($plainPassword, $this->password_hash);
    }
}
