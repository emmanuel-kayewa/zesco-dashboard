<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pp_risks', function (Blueprint $table) {
            $table->string('record_type', 10)->default('Risk')->after('risk_code');
            $table->date('created_date')->nullable()->after('status');
            $table->integer('days_open')->nullable()->after('created_date');
        });
    }

    public function down(): void
    {
        Schema::table('pp_risks', function (Blueprint $table) {
            $table->dropColumn(['record_type', 'created_date', 'days_open']);
        });
    }
};
