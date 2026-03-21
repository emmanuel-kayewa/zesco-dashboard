<?php

namespace App\Http\Controllers;

use App\Models\Directorate;
use App\Models\WayleaveEntry;
use App\Models\PpProject;
use App\Models\PpFinancial;
use App\Models\PpRisk;
use App\Services\DashboardService;
use App\Services\PpDashboardService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __construct(
        private DashboardService $dashboardService,
        private PpDashboardService $ppDashboardService,
    ) {}

    /**
     * Executive Overview Page.
     */
    public function index(Request $request)
    {
        $directorates = Directorate::active()->ordered()->get();

        // ── Build genuine directorate summary from real data ──
        $ppDir = $directorates->firstWhere('code', 'PP');
        $allProjects = PpProject::all();

        // PP financials
        $totalCommitted = round($allProjects->sum('cost_usd'), 2);
        $totalPaid = round(
            PpFinancial::where('currency', 'USD')->sum('paid_to_date'),
            2
        );
        $spendPct = $totalCommitted > 0 ? round(($totalPaid / $totalCommitted) * 100, 1) : 0;

        // PP risks
        $ppRisks = PpRisk::all();
        $totalRisks = $ppRisks->count();
        $highRisks = $ppRisks->whereIn('risk_level', ['High', 'Critical', 'high', 'critical'])->count();
        $openRisks = $ppRisks->whereIn('status', ['Open', 'open'])->count();

        // Average progress
        $avgProgress = $allProjects->count() > 0 ? round($allProjects->avg('progress_pct'), 1) : 0;

        // Sector breakdown for charts
        $sectorBreakdown = $allProjects->groupBy('sector')->map(function ($group, $sector) {
            $sectorCommitted = round($group->sum('cost_usd'), 2);
            $sectorPaid = round(
                PpFinancial::whereIn('pp_project_id', $group->pluck('id'))
                    ->where('currency', 'USD')
                    ->sum('paid_to_date'),
                2
            );
            return [
                'name' => $sector ?: 'Unknown',
                'value' => $group->count(),
                'totalCost' => $sectorCommitted,
                'totalPaid' => $sectorPaid,
                'avgProgress' => round($group->avg('progress_pct'), 1),
            ];
        })->values()->toArray();

        // RAG breakdown
        $ragBreakdown = $allProjects->groupBy('rag_status')->map(fn ($g, $rag) => [
            'name' => $rag ?: 'Unknown',
            'value' => $g->count(),
        ])->values()->toArray();

        // Risk by level
        $risksByLevel = $ppRisks->groupBy('risk_level')->map(fn ($g, $lvl) => [
            'name' => $lvl ?: 'Unknown',
            'value' => $g->count(),
        ])->values()->toArray();

        // Risk by category
        $risksByCategory = $ppRisks->groupBy('risk_category')->map(fn ($g, $cat) => [
            'name' => $cat ?: 'Unknown',
            'value' => $g->count(),
        ])->values()->toArray();

        // Build per-directorate summary — real data for PP, empty for others
        $directorateSummaries = $directorates->map(function ($d) use ($ppDir, $allProjects, $ppRisks, $totalCommitted, $totalPaid, $avgProgress, $totalRisks, $highRisks) {
            $hasData = $ppDir && $d->id === $ppDir->id;

            return [
                'id' => $d->id,
                'name' => $d->name,
                'code' => $d->code,
                'slug' => $d->slug,
                'color' => $d->color,
                'has_data' => $hasData,
                'project_count' => $hasData ? $allProjects->count() : 0,
                'total_committed' => $hasData ? $totalCommitted : 0,
                'total_paid' => $hasData ? $totalPaid : 0,
                'avg_progress' => $hasData ? $avgProgress : 0,
                'risk_count' => $hasData ? $totalRisks : 0,
                'high_risk_count' => $hasData ? $highRisks : 0,
            ];
        })->sortBy(function ($d) {
            // MD first, then alphabetical by code
            return $d['code'] === 'MD' ? '0' : ('1_' . $d['code']);
        })->values()->toArray();

        // Filter for directorate heads
        $user = $request->user();
        if ($user && $user->isDirectorateHead() && $user->directorate_id) {
            $directorateSummaries = collect($directorateSummaries)
                ->filter(fn ($d) => $d['id'] === $user->directorate_id)
                ->values()
                ->toArray();
        }

        // Top issues (Red RAG projects)
        $topIssues = $allProjects
            ->filter(fn ($p) => $p->rag_status === 'Red' || !empty($p->key_issue_summary))
            ->sortByDesc(fn ($p) => $p->rag_status === 'Red' ? 1 : 0)
            ->take(5)
            ->map(fn ($p) => [
                'id' => $p->id,
                'code' => $p->project_code,
                'name' => $p->project_name,
                'sector' => $p->sector,
                'rag' => $p->rag_status,
                'progress' => $p->progress_pct,
                'key_issue' => $p->key_issue_summary,
            ])
            ->values()
            ->toArray();

        return Inertia::render('Dashboard/Index', [
            'directorates' => $directorates,
            'directorateSummaries' => $directorateSummaries,
            'portfolio' => [
                'totalProjects' => $allProjects->count(),
                'totalCommitted' => $totalCommitted,
                'totalPaid' => $totalPaid,
                'spendPct' => $spendPct,
                'avgProgress' => $avgProgress,
                'totalRisks' => $totalRisks,
                'highRisks' => $highRisks,
                'openRisks' => $openRisks,
            ],
            'charts' => [
                'sectorBreakdown' => $sectorBreakdown,
                'ragBreakdown' => $ragBreakdown,
                'risksByLevel' => $risksByLevel,
                'risksByCategory' => $risksByCategory,
            ],
            'topIssues' => $topIssues,
        ]);
    }

    /**
     * Directorate Detail Page.
     */
    public function directorate(Request $request, Directorate $directorate)
    {
        // Enforce directorate-level access
        $user = $request->user();
        if ($user && !$user->canViewDirectorate($directorate->id)) {
            abort(403, 'You do not have permission to view this directorate.');
        }

        $from = $request->get('from') ? Carbon::parse($request->get('from')) : null;
        $to = $request->get('to') ? Carbon::parse($request->get('to')) : null;

        $directorates = Directorate::active()->ordered()->get();

        // PP directorate reads exclusively from pp_* tables — skip generic data
        $isPp = $directorate->code === 'PP';

        $detail = [];
        $anomalies = [];
        $kpis = [];
        $financials = [];
        $projects = [];
        $risks = [];
        $kpiSummary = ['total' => 0, 'completion_percentage' => 0, 'high_risk' => 0];
        $trendPayload = ['data' => [], 'forecast' => []];

        if (!$isPp) {
            $detail = $this->dashboardService->getDirectorateDetail($directorate->id, $from, $to);
            $anomalies = $this->dashboardService->detectAnomalies($directorate->id);

            $kpis = collect($detail['kpis'] ?? [])->map(fn (array $kpi) => [
                'id' => $kpi['kpi_id'] ?? null,
                'name' => $kpi['kpi_name'] ?? null,
                'slug' => $kpi['kpi_slug'] ?? null,
                'category' => $kpi['category'] ?? null,
                'value' => $kpi['value'] ?? null,
                'formatted_value' => $kpi['formatted_value'] ?? null,
                'change' => $kpi['change_percentage'] ?? null,
                'status' => $kpi['status'] ?? null,
                'unit' => $kpi['unit'] ?? null,
            ])->filter(fn ($k) => !empty($k['id']))->values()->toArray();

            $financials = collect($detail['financials'] ?? [])->map(fn (array $fin) => [
                'id' => $fin['period'] ?? null,
                'category' => 'Budget Utilization',
                'period' => $fin['period_label'] ?? ($fin['period'] ?? null),
                'budget_amount' => (float) ($fin['budget'] ?? 0),
                'actual_amount' => (float) (($fin['opex'] ?? 0) + ($fin['capex'] ?? 0)),
                'variance' => isset($fin['budget']) && (float) ($fin['budget'] ?? 0) > 0
                    ? round((((float) ($fin['budget'] ?? 0) - ((float) ($fin['opex'] ?? 0) + (float) ($fin['capex'] ?? 0))) / (float) $fin['budget']) * 100, 1)
                    : 0,
                'utilization' => (float) ($fin['budget_utilization'] ?? 0),
            ])->filter(fn ($f) => !empty($f['id']))->values()->toArray();

            $projects = collect($detail['projects'] ?? [])->map(function (array $p) {
                $status = $p['status'] ?? null;

                $uiStatus = match ($status) {
                    'completed' => 'completed',
                    'cancelled', 'on_hold' => 'at_risk',
                    'in_progress' => 'on_track',
                    default => $status ?? 'on_track',
                };

                return [
                    'id' => $p['id'] ?? null,
                    'name' => $p['name'] ?? null,
                    'status' => $uiStatus,
                    'completion_percentage' => (int) ($p['completion_percentage'] ?? 0),
                ];
            })->filter(fn ($p) => !empty($p['id']))->values()->toArray();

            $risks = collect($detail['risks'] ?? [])->map(fn (array $r) => [
                'id' => $r['id'] ?? null,
                'title' => $r['title'] ?? null,
                'description' => $r['description'] ?? '',
                'impact' => $r['impact'] ?? null,
                'likelihood' => $r['likelihood'] ?? null,
                'risk_level' => $r['risk_level'] ?? ($r['level'] ?? null),
                'status' => $r['status'] ?? null,
            ])->filter(fn ($r) => !empty($r['id']))->values()->toArray();

            $kpiSummary = [
                'total' => count($kpis),
                'completion_percentage' => count($kpis) > 0
                    ? round((collect($kpis)->where('status', 'healthy')->count() / max(count($kpis), 1)) * 100)
                    : 0,
                'high_risk' => collect($risks)->whereIn('risk_level', ['high', 'critical'])->count(),
            ];

            if (!empty($kpis)) {
                $trendKpiId = $kpis[0]['id'];
                $trend = $this->dashboardService->getKpiTrend($trendKpiId, $directorate->id, 12);
                $forecast = $this->dashboardService->forecastKpi($trendKpiId, $directorate->id, 3);
                $trendPayload = [
                    'data' => collect($trend)->map(fn ($p) => [
                        'date' => $p['label'] ?? ($p['period'] ?? null),
                        'value' => $p['value'] ?? null,
                    ])->values()->toArray(),
                    'forecast' => collect($forecast)->map(fn ($p) => [
                        'date' => $p['label'] ?? ($p['period'] ?? null),
                        'value' => $p['value'] ?? null,
                    ])->values()->toArray(),
                ];
            }
        }

        // ── PP Portfolio — Minimal summary for redirect card ────────
        $ppPortfolio = null;
        if ($isPp) {
            $allProjects    = PpProject::all();
            $totalCommitted = round($allProjects->sum('cost_usd'), 2);
            $totalPaid      = round(PpFinancial::where('currency', 'USD')->sum('paid_to_date'), 2);
            $spendPct       = $totalCommitted > 0 ? round(($totalPaid / $totalCommitted) * 100, 2) : 0;

            $ppPortfolio = [
                'boardSummary' => [
                    'totalProjects'  => $allProjects->count(),
                    'totalCommitted' => $totalCommitted,
                    'spendPct'       => $spendPct,
                ],
            ];
        }

        return Inertia::render('Dashboard/DirectorateDetail', [
            'directorate' => $directorate,
            'directorates' => $directorates,
            'kpis' => $kpis,
            'kpiSummary' => $kpiSummary,
            'financials' => $financials,
            'projects' => $projects,
            'risks' => $risks,
            'trend' => $trendPayload,
            'detail' => $detail,
            'anomalies' => $anomalies,
            'ppPortfolio' => $ppPortfolio,
            'filters' => [
                'from' => $from?->format('Y-m-d'),
                'to' => $to?->format('Y-m-d'),
            ],
        ]);
    }

    /**
     * KPI Trend API endpoint.
     */
    public function kpiTrend(Request $request)
    {
        $request->validate([
            'kpi_id' => 'required|integer|exists:kpis,id',
            'directorate_id' => 'required|integer|exists:directorates,id',
            'periods' => 'nullable|integer|min:3|max:36',
        ]);

        // Enforce directorate-level access
        $user = $request->user();
        if ($user && !$user->canViewDirectorate((int) $request->directorate_id)) {
            abort(403, 'You do not have permission to view this directorate\'s data.');
        }

        $trend = $this->dashboardService->getKpiTrend(
            $request->kpi_id,
            $request->directorate_id,
            $request->get('periods', 12)
        );

        $forecast = $this->dashboardService->forecastKpi(
            $request->kpi_id,
            $request->directorate_id,
            3
        );

        return response()->json([
            'trend' => $trend,
            'forecast' => $forecast,
        ]);
    }

    /**
     * Directorate Comparison.
     */
    public function comparison(Request $request)
    {
        $directorates = Directorate::active()->ordered()->get();
        $allKpis = \App\Models\Kpi::active()->orderBy('name')->get();

        // Build comparison data from executive summary
        $summary = $this->dashboardService->getExecutiveSummary();
        $comparison = collect($summary['directorates'] ?? [])->map(function ($d) {
            return [
                'id' => $d['id'],
                'name' => $d['name'],
                'code' => $d['code'],
                'slug' => $d['slug'],
                'color' => $d['color'] ?? '#3b82f6',
                'completion' => $d['completion_percentage'] ?? 0,
                'revenue' => $d['revenue'] ?? 0,
                'budget_utilization' => $d['budget_utilization'] ?? 0,
                'risk_score' => ($d['high_risk_count'] ?? 0) * 5 + ($d['risk_count'] ?? 0),
                'project_count' => $d['project_count'] ?? 0,
                'risk_count' => $d['risk_count'] ?? 0,
                'high_risk_count' => $d['high_risk_count'] ?? 0,
                'employees' => $d['employees'] ?? 0,
                'status' => ($d['completion_percentage'] ?? 0) >= 75 ? 'healthy' : (($d['completion_percentage'] ?? 0) >= 50 ? 'warning' : 'critical'),
            ];
        })->values()->toArray();

        // Filter for directorate heads
        $user = $request->user();
        if ($user && $user->isDirectorateHead() && $user->directorate_id) {
            $comparison = collect($comparison)
                ->filter(fn ($d) => $d['id'] === $user->directorate_id)
                ->values()
                ->toArray();
        }

        return Inertia::render('Dashboard/Comparison', [
            'comparison' => $comparison,
            'directorates' => $directorates,
        ]);
    }
}
