<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PpProject extends Model
{
    protected $fillable = [
        'project_code',
        'project_name',
        'sector',
        'sub_sector',
        'project_stage',
        'status',
        'programme',
        'province',
        'district',
        'contractor',
        'developer',
        'cost_usd',
        'cost_zmw',
        'capacity_mw',
        'commissioned_mw',
        'progress_pct',
        'cod_planned',
        'key_issue_summary',
        'last_update_date',
        'notes',
        'entered_by',
        // REMs classification
        'energy_type',
        'renewable_flag',
        'market_segment',
        'ownership_model',
        'owner_group',
        'owner_entity',
        'is_ipp',
        'commissioned_mw_to_date',
        'grid_connected',
        'cod_actual',
        'commissioned_date',
        'owner_subsidiary_name',
        'owner_subsidiary_flag',
        'commissioned_capacity_mw',
        'lifecycle_phase',
        // PMO / Earned-value
        'project_manager',
        'planned_start',
        'planned_finish',
        'forecast_finish',
        'approved_budget',
        'committed_cost',
        'actual_spend',
        'forecast_at_completion',
        'next_decision',
    ];

    protected $casts = [
        'cost_usd'                => 'decimal:2',
        'cost_zmw'                => 'decimal:2',
        'capacity_mw'             => 'decimal:3',
        'commissioned_mw'         => 'decimal:3',
        'progress_pct'            => 'decimal:2',
        'cod_planned'             => 'date',
        'last_update_date'        => 'date',
        // REMs
        'renewable_flag'          => 'boolean',
        'is_ipp'                  => 'boolean',
        'commissioned_mw_to_date' => 'decimal:3',
        'grid_connected'          => 'boolean',
        'cod_actual'              => 'date',
        'commissioned_date'       => 'date',
        'owner_subsidiary_flag'   => 'boolean',
        'commissioned_capacity_mw'=> 'decimal:3',
        // PMO
        'planned_start'           => 'date',
        'planned_finish'          => 'date',
        'forecast_finish'         => 'date',
        'approved_budget'         => 'decimal:2',
        'committed_cost'          => 'decimal:2',
        'actual_spend'            => 'decimal:2',
        'forecast_at_completion'  => 'decimal:2',
    ];

    // ── Relationships ──────────────────────────────────────

    public function enteredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'entered_by');
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(PpMilestone::class);
    }

    public function financials(): HasMany
    {
        return $this->hasMany(PpFinancial::class);
    }

    public function risks(): HasMany
    {
        return $this->hasMany(PpRisk::class);
    }

    public function safeguards(): HasMany
    {
        return $this->hasMany(PpSafeguard::class);
    }

    public function gridImpactStudies(): HasMany
    {
        return $this->hasMany(PpGridImpactStudy::class);
    }

    public function workstreams(): HasMany
    {
        return $this->hasMany(PpWorkstream::class);
    }

    // ── Scopes ─────────────────────────────────────────────

    public function scopeBySector($query, string $sector)
    {
        return $query->where('sector', $sector);
    }

    public function scopeByProjectStage($query, string $stage)
    {
        return $query->where('project_stage', $stage);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByLifecyclePhase($query, string $phase)
    {
        return $query->where('lifecycle_phase', $phase);
    }
}
