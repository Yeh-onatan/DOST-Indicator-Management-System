<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IndicatorMandatoryAssignment extends Model
{
    protected $fillable = [
        'objective_id',
        'assignment_type',
        'region_id',
        'office_id',
        'agency_id',
    ];

    public function objective(): BelongsTo
    {
        return $this->belongsTo(Objective::class, 'objective_id');
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(PhilippineRegion::class, 'region_id');
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(DOSTAgency::class, 'agency_id');
    }
}
