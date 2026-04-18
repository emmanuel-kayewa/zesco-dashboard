<?php

use App\Models\PpFinancial;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $financials = DB::table('pp_financials')
            ->orderBy('id')
            ->get(['id', 'finance_code']);

        $counter = 1;
        foreach ($financials as $record) {
            $newCode = 'FIN-' . str_pad($counter, 4, '0', STR_PAD_LEFT);
            DB::table('pp_financials')
                ->where('id', $record->id)
                ->update(['finance_code' => $newCode]);
            $counter++;
        }
    }

    public function down(): void
    {
        // Cannot reliably restore original codes
    }
};
