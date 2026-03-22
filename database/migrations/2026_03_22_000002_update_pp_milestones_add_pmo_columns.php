<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pp_milestones', function (Blueprint $table) {
            $table->string('category', 40)->nullable()->after('milestone');
            $table->date('baseline_date')->nullable()->after('category');
            $table->date('forecast_date')->nullable()->after('baseline_date');
            $table->decimal('weight_pct', 5, 2)->nullable()->after('forecast_date');
            $table->integer('delay_days')->nullable()->after('weight_pct');
            $table->string('owner')->nullable()->after('delay_days');
        });
    }

    public function down(): void
    {
        Schema::table('pp_milestones', function (Blueprint $table) {
            $table->dropColumn([
                'category', 'baseline_date', 'forecast_date',
                'weight_pct', 'delay_days', 'owner',
            ]);
        });
    }
};
