<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PpWeeklyReport extends Model
{
    protected $fillable = [
        'week_number',
        'year',
        'report_date',
        'notes',
        'user_id',
    ];

    protected $casts = [
        'week_number' => 'integer',
        'year'        => 'integer',
        'report_date' => 'date',
    ];

    // ── Relationships ──────────────────────────────────────

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(PpReportSection::class)->orderBy('sort_order');
    }

    // ── Helpers ────────────────────────────────────────────

    public function totalProjectEntries(): int
    {
        return $this->sections->sum(fn ($s) => $s->projectEntries->count());
    }

    public function totalMw(): float
    {
        return $this->sections->sum(fn ($s) => $s->projectEntries->sum('size_mw'));
    }
}
