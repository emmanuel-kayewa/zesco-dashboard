<?php

namespace App\Console\Commands;

use App\Models\PortfolioSnapshot;
use App\Models\PpFinancial;
use App\Models\PpProject;
use App\Models\PpRisk;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SnapshotPortfolioCommand extends Command
{
    protected $signature = 'portfolio:snapshot';

    protected $description = 'Capture a daily snapshot of portfolio aggregate metrics';

    public function handle(): int
    {
        $today = Carbon::today()->toDateString();

        $allProjects = PpProject::all();

        $totalCommitted = round($allProjects->sum('cost_usd'), 2);
        $totalPaid = round(
            PpFinancial::where('currency', 'USD')->sum('paid_to_date'),
            2
        );
        $avgProgress = $allProjects->count() > 0
            ? round($allProjects->avg('progress_pct'), 1)
            : 0;

        $ppRisks = PpRisk::all();
        $totalRisks = $ppRisks->count();
        $highRisks = $ppRisks->whereIn('risk_level', ['High', 'Critical', 'high', 'critical'])->count();

        PortfolioSnapshot::updateOrCreate(
            ['snapshot_date' => $today],
            [
                'total_projects'  => $allProjects->count(),
                'total_committed' => $totalCommitted,
                'total_paid'      => $totalPaid,
                'avg_progress'    => $avgProgress,
                'total_risks'     => $totalRisks,
                'high_risks'      => $highRisks,
            ]
        );

        $this->info("Portfolio snapshot saved for {$today}.");

        return self::SUCCESS;
    }
}
