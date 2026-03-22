<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PpWorkstream extends Model
{
    protected $fillable = [
        'workstream_code',
        'pp_project_id',
        'workstream',
        'planned_pct',
        'actual_pct',
        'variance_pct',
        'status',
        'comments',
        'entered_by',
    ];

    protected $casts = [
        'planned_pct'  => 'decimal:2',
        'actual_pct'   => 'decimal:2',
        'variance_pct' => 'decimal:2',
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
