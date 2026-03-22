<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pp_projects', function (Blueprint $table) {
            // ── REMs classification columns ────────────────────
            $table->string('energy_type', 30)->nullable()->after('notes');
            $table->boolean('renewable_flag')->default(false)->after('energy_type');
            $table->string('market_segment')->nullable()->after('renewable_flag');
            $table->string('ownership_model')->nullable()->after('market_segment');
            $table->string('owner_group')->nullable()->after('ownership_model');
            $table->string('owner_entity')->nullable()->after('owner_group');
            $table->boolean('is_ipp')->default(false)->after('owner_entity');
            $table->decimal('commissioned_mw_to_date', 10, 3)->nullable()->after('is_ipp');
            $table->boolean('grid_connected')->default(false)->after('commissioned_mw_to_date');
            $table->date('cod_actual')->nullable()->after('grid_connected');
            $table->date('commissioned_date')->nullable()->after('cod_actual');
            $table->string('owner_subsidiary_name')->nullable()->after('commissioned_date');
            $table->boolean('owner_subsidiary_flag')->default(false)->after('owner_subsidiary_name');
            $table->decimal('commissioned_capacity_mw', 10, 3)->nullable()->after('owner_subsidiary_flag');
            $table->string('lifecycle_phase', 40)->nullable()->after('commissioned_capacity_mw');

            // ── PMO / Earned-value columns ─────────────────────
            $table->string('project_manager')->nullable()->after('lifecycle_phase');
            $table->date('planned_start')->nullable()->after('project_manager');
            $table->date('planned_finish')->nullable()->after('planned_start');
            $table->date('forecast_finish')->nullable()->after('planned_finish');
            $table->decimal('approved_budget', 18, 2)->nullable()->after('forecast_finish');
            $table->decimal('committed_cost', 18, 2)->nullable()->after('approved_budget');
            $table->decimal('actual_spend', 18, 2)->nullable()->after('committed_cost');
            $table->decimal('forecast_at_completion', 18, 2)->nullable()->after('actual_spend');
            $table->text('next_decision')->nullable()->after('forecast_at_completion');

            // ── Drop obsolete columns ──────────────────────────
            $table->dropColumn(['funder', 'funding_type', 'rag_status']);
        });
    }

    public function down(): void
    {
        Schema::table('pp_projects', function (Blueprint $table) {
            $table->dropColumn([
                'energy_type', 'renewable_flag', 'market_segment', 'ownership_model',
                'owner_group', 'owner_entity', 'is_ipp', 'commissioned_mw_to_date',
                'grid_connected', 'cod_actual', 'commissioned_date',
                'owner_subsidiary_name', 'owner_subsidiary_flag',
                'commissioned_capacity_mw', 'lifecycle_phase',
                'project_manager', 'planned_start', 'planned_finish', 'forecast_finish',
                'approved_budget', 'committed_cost', 'actual_spend',
                'forecast_at_completion', 'next_decision',
            ]);

            $table->string('funder')->nullable()->after('developer');
            $table->string('funding_type', 30)->nullable()->after('funder');
            $table->string('rag_status', 10)->default('Amber')->after('last_update_date');
        });
    }
};
