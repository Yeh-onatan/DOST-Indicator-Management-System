<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSetting extends Model
{
    protected $fillable = [
        'user_id',
        'theme',
        'density',
        'default_landing_page',
        'table_page_size',
        'default_agency',
        'default_year',
        'default_quarter',
        'number_format',
        'date_format',
        'progress_display',
        'notifications',
        'export_presets',
    ];

    protected $casts = [
        'notifications'   => 'array',
        'export_presets'  => 'array',
        'default_year'    => 'integer',
        'default_quarter' => 'integer',
        'table_page_size' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
