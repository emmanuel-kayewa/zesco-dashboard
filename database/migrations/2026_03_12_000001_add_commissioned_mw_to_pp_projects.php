<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pp_projects', function (Blueprint $table) {
            $table->decimal('commissioned_mw', 10, 3)->nullable()->after('capacity_mw');
        });
    }

    public function down(): void
    {
        Schema::table('pp_projects', function (Blueprint $table) {
            $table->dropColumn('commissioned_mw');
        });
    }
};
