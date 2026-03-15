<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PpGridImpactStudy extends Model
{
    protected $table = 'pp_grid_impact_studies';

    protected $fillable = [
        'study_code',
        'pp_project_id',
        'study_type',
        'name',
        'capacity_mw',
        'developer',
        'technology',
        'project_area',
        'point_of_connection',
        'progress_pct',
        'stage_received',
        'stage_not_started',
        'stage_under_review',
        'stage_pending_client',
        'stage_revisions',
        'stage_approved',
        'notes',
        'entered_by',
    ];

    protected $casts = [
        'capacity_mw'          => 'decimal:3',
        'progress_pct'         => 'decimal:2',
        'stage_received'       => 'boolean',
        'stage_not_started'    => 'boolean',
        'stage_under_review'   => 'boolean',
        'stage_pending_client' => 'boolean',
        'stage_revisions'      => 'boolean',
        'stage_approved'       => 'boolean',
    ];

    // ── Relationships ──────────────────────────────────────

    public function project(): BelongsTo
    {
        return $this->belongsTo(PpProject::class, 'pp_project_id');
    }

    public function enteredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'entered_by');
    }

    // ── Accessors ──────────────────────────────────────────

    /**
     * Returns the furthest-reached stage name.
     */
    public function getCurrentStageAttribute(): string
    {
        if ($this->stage_approved) return 'Approved';
        if ($this->stage_revisions) return 'Revisions';
        if ($this->stage_pending_client) return 'Pending Client';
        if ($this->stage_under_review) return 'Under Review';
        if ($this->stage_received) return 'Received';
        if ($this->stage_not_started) return 'Not Started';
        return 'Not Started';
    }

    // ── Scopes ─────────────────────────────────────────────

    public function scopeByType($query, string $type)
    {
        return $query->where('study_type', $type);
    }

    public function scopeApproved($query)
    {
        return $query->where('stage_approved', true);
    }
}
