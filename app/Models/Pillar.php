<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pillar extends Model
{
    protected $fillable = ['value', 'name', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function objectives(): HasMany
    {
        return $this->hasMany(Objective::class);
    }
}
