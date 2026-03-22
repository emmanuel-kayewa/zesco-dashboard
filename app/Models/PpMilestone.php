<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PpMilestone extends Model
{
    protected $fillable = [
        'milestone_code',
        'pp_project_id',
        'milestone',
        'category',
        'baseline_date',
        'forecast_date',
        'actual_date',
        'weight_pct',
        'delay_days',
        'owner',
        'status',
        'notes',
        'entered_by',
    ];

    protected $casts = [
        'baseline_date' => 'date',
        'forecast_date' => 'date',
        'actual_date'   => 'date',
        'weight_pct'    => 'decimal:2',
        'delay_days'    => 'integer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(PpProject::class, 'pp_project_id');
    }

    public function enteredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'entered_by');
    }
}
