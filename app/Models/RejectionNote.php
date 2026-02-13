<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RejectionNote extends Model
{
    protected $fillable = [
        'objective_id',
        'rejected_by_user_id',
        'visible_to_user_id',
        'note',
    ];

    public function objective(): BelongsTo
    {
        return $this->belongsTo(Objective::class, 'objective_id');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by_user_id');
    }

    public function visibleTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'visible_to_user_id');
    }
}
