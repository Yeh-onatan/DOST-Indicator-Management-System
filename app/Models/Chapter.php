<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chapter extends Model
{
    use HasFactory;

    protected $fillable = [
        'category',
        'code',
        'title',
        'outcome',
        'description',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function objectives()
    {
        return $this->hasMany(Objective::class);
    }

    public function indicatorTemplates()
    {
        return $this->hasMany(IndicatorTemplate::class);
    }
}
