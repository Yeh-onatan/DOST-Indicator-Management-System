<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportingWindow extends Model
{
    use HasFactory;

    protected $fillable = [
        'year', 'quarter', 'opens_at', 'due_at', 'grace_days', 'lock_after_close',
    ];

    protected $casts = [
        'opens_at' => 'datetime',
        'due_at' => 'datetime',
        'lock_after_close' => 'boolean',
        'year' => 'integer',
        'quarter' => 'integer',
        'grace_days' => 'integer',
    ];
}

