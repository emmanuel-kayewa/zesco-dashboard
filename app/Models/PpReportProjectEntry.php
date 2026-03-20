<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PpReportProjectEntry extends Model
{
    protected $fillable = [
        'pp_report_section_id',
        'name',
        'location',
        'developer',
        'size_mw',
        'project_type',
        'est_completion',
        'notes',
        'sort_order',
    ];

    protected $casts = [
        'size_mw'    => 'decimal:3',
        'sort_order' => 'integer',
    ];

    public function section(): BelongsTo
    {
        return $this->belongsTo(PpReportSection::class, 'pp_report_section_id');
    }
}
