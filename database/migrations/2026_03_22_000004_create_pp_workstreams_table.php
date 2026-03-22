<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pp_workstreams', function (Blueprint $table) {
            $table->id();
            $table->string('workstream_code', 30)->unique();
            $table->foreignId('pp_project_id')->constrained('pp_projects')->cascadeOnDelete();
            $table->string('workstream', 40);                  // Engineering, Procurement, Construction, Commissioning
            $table->decimal('planned_pct', 5, 2)->nullable();
            $table->decimal('actual_pct', 5, 2)->nullable();
            $table->decimal('variance_pct', 6, 2)->nullable();
            $table->string('status', 30)->default('On Track');  // On Track, At Risk, Delayed
            $table->text('comments')->nullable();
            $table->foreignId('entered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('pp_project_id');
            $table->index('workstream');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pp_workstreams');
    }
};
