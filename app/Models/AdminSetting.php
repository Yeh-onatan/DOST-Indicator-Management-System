<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'pdf_logo_path',
        'pdf_header',
        'pdf_footer',
        'org_name',
        'org_logo_path',
        'theme_accent',
        'timezone',
        'locale',
        'archive_years',
        'regions_roles',
        'compliance',
    ];

    protected $casts = [
        'regions_roles' => 'array',
        'compliance' => 'array',
        'archive_years' => 'integer',
    ];
}

