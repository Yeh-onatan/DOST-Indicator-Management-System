<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Office extends Model
{
    protected $fillable = [
        'code',
        'name',
        'parent_office_id',
        'type', // CO/HO, RO, PSTO
        'region_id',
        'head_user_id',
        'is_active',
        'agency_id',
    ];

    protected $attributes = [
        'region_id' => null,
    ];

    /**
     * Get the Head of Office (User)
     */
    public function head(): BelongsTo
    {
        return $this->belongsTo(User::class, 'head_user_id');
    }

    /**
     * Get the parent office (for ROs: Central Office, for PSTOs: their RO)
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Office::class, 'parent_office_id');
    }

    /**
     * Get child offices (for Central Office: all ROs, for ROs: all PSTOs under them)
     */
    public function children(): HasMany
    {
        return $this->hasMany(Office::class, 'parent_office_id');
    }

    /**
     * Get all descendant offices recursively
     */
    public function descendants()
    {
        return $this->children()->with('descendants');
    }

    /**
     * Get the regional office (for PSTOs, return parent RO; for ROs, return self)
     */
    public function regionalOffice(): ?Office
    {
        if ($this->type === 'PSTO') {
            return $this->parent;
        } elseif ($this->type === 'RO') {
            return $this;
        }
        return null;
    }

    /**
     * Get all PSTOs under this office (for ROs only)
     */
    public function pstos(): HasMany
    {
        return $this->hasMany(Office::class, 'parent_office_id')->where('type', 'PSTO');
    }

    /**
     * Scopes for filtering
     */
    public function scopeRegionalOffices($query)
    {
        return $query->where('type', 'RO');
    }

    public function scopePSTOs($query)
    {
        return $query->where('type', 'PSTO');
    }

    public function scopeHeadOffice($query)
    {
        return $query->whereIn('type', ['CO', 'HO']);
    }

    /**
     * Relationships to other models
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function objectives(): HasMany
    {
        return $this->hasMany(Objective::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(PhilippineRegion::class, 'region_id');
    }

    /**
     * Helpers for hierarchy checks
     */
    public function isPSTO(): bool
    {
        return $this->type === 'PSTO';
    }

    public function isRO(): bool
    {
        return $this->type === 'RO';
    }

    /**
     * Check if this is the Head Office / Central Office
     */
    public function isHO(): bool
    {
        return in_array($this->type, ['CO', 'HO']);
    }

    public function isCO(): bool
    {
        return $this->isHO();
    }
}