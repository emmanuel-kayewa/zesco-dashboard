<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pp_projects', function (Blueprint $table) {
            $table->renameColumn('status', 'project_stage');
        });

        Schema::table('pp_projects', function (Blueprint $table) {
            $table->string('status', 30)->nullable()->after('project_stage');
        });
    }

    public function down(): void
    {
        Schema::table('pp_projects', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('pp_projects', function (Blueprint $table) {
            $table->renameColumn('project_stage', 'status');
        });
    }
};
