<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IndicatorHistory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'objective_id',
        'action',
        'old_values',
        'new_values',
        'rejection_note',
        'actor_user_id',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function objective(): BelongsTo
    {
        return $this->belongsTo(Objective::class, 'objective_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id')->withDefault();
    }

    /**
     * Scope for timeline display - get histories for a specific objective
     */
    public function scopeForObjective($query, $objectiveId)
    {
        return $query->where('objective_id', $objectiveId)->latest();
    }

    /**
     * Get action label for display
     */
    public function getActionLabelAttribute(): string
    {
        return match($this->action) {
            'submit_to_ro' => 'Submitted to Regional Office',
            'submit_to_ho' => 'Submitted to Head Office',
            'approve' => 'Approved',
            'reject' => 'Returned',
            'create' => 'Created',
            'update' => 'Updated',
            default => ucfirst(str_replace('_', ' ', $this->action)),
        };
    }

    /**
     * Get color class for action type
     */
    public function getColorAttribute(): string
    {
        return match($this->action) {
            'reject' => 'red',
            'approve' => 'green',
            'submit_to_ro', 'submit_to_ho' => 'blue',
            default => 'gray',
        };
    }
}
