<?php

namespace App\Console\Commands;

use App\Models\FinancialEntry;
use App\Models\Incident;
use App\Models\KpiEntry;
use App\Models\Project;
use App\Models\Risk;
use App\Models\Setting;
use App\Models\SimulationLog;
use App\Services\AiAnalysisService;
use Illuminate\Console\Command;

class PurgeSimulationDataCommand extends Command
{
    protected $signature = 'dashboard:purge-simulation
                            {--force : Skip confirmation prompt}';

    protected $description = 'Delete all simulated data, disable simulation mode, and clear AI caches';

    public function handle(AiAnalysisService $ai): int
    {
        $this->warn('This will permanently delete ALL simulated data from the database.');
        $this->newLine();

        // Preview counts
        $counts = [
            'kpi_entries' => KpiEntry::where('source', 'simulation')->count(),
            'financial_entries' => FinancialEntry::where('source', 'simulation')->count(),
            'projects' => Project::where('source', 'simulation')->count(),
            'risks' => Risk::where('source', 'simulation')->count(),
            'incidents' => Incident::where('source', 'simulation')->count(),
            'simulation_logs' => SimulationLog::count(),
        ];

        $this->table(['Table', 'Rows to delete'], collect($counts)->map(
            fn($count, $table) => [$table, $count]
        )->toArray());

        $total = array_sum($counts);

        if ($total === 0) {
            $this->info('No simulated data found. Nothing to purge.');
            return self::SUCCESS;
        }

        if (!$this->option('force') && !$this->confirm("Delete {$total} rows across 6 tables?")) {
            $this->info('Aborted.');
            return self::SUCCESS;
        }

        // Phase 1: Delete simulated rows
        $this->info('Deleting simulated data...');

        $deleted = [];
        $deleted['kpi_entries'] = KpiEntry::where('source', 'simulation')->delete();
        $deleted['financial_entries'] = FinancialEntry::where('source', 'simulation')->delete();
        $deleted['projects'] = Project::where('source', 'simulation')->delete();
        $deleted['risks'] = Risk::where('source', 'simulation')->delete();
        $deleted['incidents'] = Incident::where('source', 'simulation')->delete();

        SimulationLog::truncate();
        $deleted['simulation_logs'] = $counts['simulation_logs'];

        $this->table(['Table', 'Rows deleted'], collect($deleted)->map(
            fn($count, $table) => [$table, $count]
        )->toArray());

        // Phase 2: Disable simulation in settings
        $this->info('Disabling simulation mode...');
        Setting::setValue('simulation_enabled', 'false', 'boolean');
        Setting::setValue('data_source', 'manual', 'string');
        $this->line('  simulation_enabled → false');
        $this->line('  data_source → manual');

        // Phase 3: Clear AI caches
        $this->info('Clearing AI caches...');
        $ai->clearCache();
        $this->line('  AI response caches flushed.');

        $this->newLine();
        $this->info('Done. All simulated data has been purged.');
        $this->newLine();
        $this->comment('Recommended next steps:');
        $this->comment('  1. Update .env: DASHBOARD_DATA_SOURCE=manual');
        $this->comment('  2. Update .env: DASHBOARD_SIMULATION_ENABLED=false');
        $this->comment('  3. Run: php artisan portfolio:snapshot');
        $this->comment('  4. Run: php artisan optimize:clear');

        return self::SUCCESS;
    }
}
