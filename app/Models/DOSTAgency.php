<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DOSTAgency extends Model
{
    use SoftDeletes;

    protected $table = 'agencies';  // Database table name remains 'agencies'

    protected $fillable = [
        'code',
        'name',
        'acronym',
        'description',
        'cluster',
        'is_active',
        'head_user_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function objectives(): HasMany
    {
        return $this->hasMany(Objective::class, 'agency_id');
    }

    public function head(): BelongsTo
    {
        return $this->belongsTo(User::class, 'head_user_id');
    }
}
