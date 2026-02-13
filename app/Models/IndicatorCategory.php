<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class IndicatorCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'description',
        'requires_chapter',
        'is_active',
        'display_order',
        'created_by',
        'is_mandatory',
    ];

    protected $casts = [
        'requires_chapter' => 'boolean',
        'is_active' => 'boolean',
        'is_mandatory' => 'boolean',
    ];

    /**
     * Boot method to auto-generate slug from name if not provided
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });

        static::updating(function ($category) {
            if (empty($category->slug) || $category->isDirty('name')) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    /**
     * Get the user who created this category
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the dynamic fields for this category.
     */
    public function fields(): HasMany
    {
        return $this->hasMany(CategoryField::class, 'category_id')->orderBy('display_order');
    }

    /**
     * Scope to filter categories visible to a user
     */
    public function scopeVisibleTo($query, User $user)
    {
        return $query;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}