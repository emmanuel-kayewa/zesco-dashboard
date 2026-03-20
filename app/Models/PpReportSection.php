<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PpReportSection extends Model
{
    protected $fillable = [
        'pp_weekly_report_id',
        'section_number',
        'title',
        'section_type',
        'sort_order',
    ];

    protected $casts = [
        'section_number' => 'integer',
        'sort_order'     => 'integer',
    ];

    // ── Relationships ──────────────────────────────────────

    public function report(): BelongsTo
    {
        return $this->belongsTo(PpWeeklyReport::class, 'pp_weekly_report_id');
    }

    public function projectEntries(): HasMany
    {
        return $this->hasMany(PpReportProjectEntry::class)->orderBy('sort_order');
    }

    public function netMeteringEntries(): HasMany
    {
        return $this->hasMany(PpReportNetMeteringEntry::class)->orderBy('sort_order');
    }

    // ── Helpers ────────────────────────────────────────────

    public function isNetMetering(): bool
    {
        return $this->section_type === 'net_metering';
    }
}
