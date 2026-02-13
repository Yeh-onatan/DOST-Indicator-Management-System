<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CategoryField extends Model
{
    protected $fillable = [
        'category_id',
        'field_name',
        'field_label',
        'field_type',
        'db_column',
        'options',
        'is_required',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'options' => 'array',
        'is_required' => 'boolean',
        'is_active' => 'boolean',
        'display_order' => 'integer',
    ];

    /**
     * Get the category that owns this field.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(IndicatorCategory::class, 'category_id');
    }

    /**
     * Scope to only include active fields.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by display order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order');
    }

    /**
     * Get the available database columns that can be mapped.
     */
    public static function getAvailableColumns(): array
    {
        return [
            'objective_result' => 'Outcome/Pillar',
            'description' => 'Output/Strategy',
            'program_name' => 'Program Name',
            'indicator_type' => 'Indicator Type',
            'assumptions_risk' => 'Definition/Assumptions',
            'accomplishments' => 'Actual',
            'pc_secretariat_remarks' => 'Remarks',
            'status' => 'Status',
            'prexc_code' => 'PREXC Code',
            'sp_id' => 'SP ID',
            'dost_agency' => 'DOST Agency',
            'priority' => 'Priority',
            'review_notes' => 'Review Notes',
        ];
    }

    /**
     * Get the available field types.
     */
    public static function getFieldTypes(): array
    {
        return [
            'text' => 'Text Input',
            'textarea' => 'Text Area',
            'select' => 'Dropdown Select',
            'number' => 'Number Input',
        ];
    }
}
