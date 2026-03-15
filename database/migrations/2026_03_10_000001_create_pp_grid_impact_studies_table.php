<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pp_grid_impact_studies', function (Blueprint $table) {
            $table->id();
            $table->string('study_code', 30)->unique();
            $table->foreignId('pp_project_id')->nullable()->constrained('pp_projects')->cascadeOnDelete();
            $table->string('study_type', 20);            // 'report' or 'inception'
            $table->string('name');
            $table->decimal('capacity_mw', 10, 3)->nullable();
            $table->string('developer')->nullable();
            $table->string('technology', 100)->nullable();
            $table->string('project_area')->nullable();
            $table->string('point_of_connection')->nullable();
            $table->decimal('progress_pct', 5, 2)->default(0);
            $table->boolean('stage_received')->default(false);
            $table->boolean('stage_not_started')->default(false);
            $table->boolean('stage_under_review')->default(false);
            $table->boolean('stage_pending_client')->default(false);
            $table->boolean('stage_revisions')->default(false);
            $table->boolean('stage_approved')->default(false);
            $table->text('notes')->nullable();
            $table->foreignId('entered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('pp_project_id');
            $table->index('study_type');
            $table->index('stage_approved');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pp_grid_impact_studies');
    }
};
