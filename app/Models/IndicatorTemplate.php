<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IndicatorTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'name', 'description', 'category',
        'baseline_required', 'mov_required',
        'allowed_value_type', 'is_active',
        'chapter_id',
    ];

    public function chapter()
    {
        return $this->belongsTo(Chapter::class);
    }
}
