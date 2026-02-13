<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhilippineRegion extends Model
{
    protected $table = 'regions';  // Database table name remains 'regions'

    protected $fillable = [
        'code',
        'name',
        'description',
        'is_active',
        'order_index', // [NEW] For sorting
        'director_id', // [NEW] For Regional Director assignment
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function director(): BelongsTo
    {
        return $this->belongsTo(User::class, 'director_id');
    }

    public function offices(): HasMany
    {
        return $this->hasMany(Office::class, 'region_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'region_id');
    }

    public function objectives(): HasMany
    {
        return $this->hasMany(Objective::class, 'region_id');
    }
}