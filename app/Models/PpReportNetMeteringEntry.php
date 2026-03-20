<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PpReportNetMeteringEntry extends Model
{
    protected $fillable = [
        'pp_report_section_id',
        'key_initiative',
        'status_value',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function section(): BelongsTo
    {
        return $this->belongsTo(PpReportSection::class, 'pp_report_section_id');
    }
}
