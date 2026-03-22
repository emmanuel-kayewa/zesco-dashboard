<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PpRisk extends Model
{
    protected $fillable = [
        'risk_code',
        'record_type',
        'pp_project_id',
        'risk_category',
        'risk_description',
        'likelihood',
        'impact',
        'severity',
        'risk_level',
        'mitigation',
        'owner',
        'due_date',
        'status',
        'created_date',
        'days_open',
        'notes',
        'entered_by',
    ];

    protected $casts = [
        'due_date'     => 'date',
        'created_date' => 'date',
        'likelihood'   => 'integer',
        'impact'       => 'integer',
        'severity'     => 'integer',
        'days_open'    => 'integer',
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
