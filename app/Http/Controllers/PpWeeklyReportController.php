<?php

namespace App\Http\Controllers;

use App\Http\Requests\PpWeeklyReportRequest;
use App\Models\AuditLog;
use App\Models\Directorate;
use App\Models\PpWeeklyReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PpWeeklyReportController extends Controller
{
    private function enforcePpAccess(Request $request): void
    {
        $user = $request->user();
        if (!$user) {
            abort(403);
        }
        if ($user->isAdmin()) {
            return;
        }
        $pp = Directorate::where('code', 'PP')->firstOrFail();
        if (!$user->isDirectorateHead() || (int) $user->directorate_id !== (int) $pp->id) {
            abort(403, 'You do not have permission to manage PP weekly reports.');
        }
    }

    /**
     * List all weekly reports (accessible to all authenticated users).
     */
    public function index(Request $request)
    {
        $reports = PpWeeklyReport::with('author:id,name')
            ->withCount(['sections'])
            ->orderByDesc('year')
            ->orderByDesc('week_number')
            ->paginate(20)
            ->withQueryString();

        // Load total project counts for each report
        $reports->getCollection()->transform(function ($report) {
            $report->loadMissing('sections.projectEntries');
            $report->total_projects = $report->totalProjectEntries();
            $report->total_mw = $report->totalMw();
            unset($report->sections);
            return $report;
        });

        return Inertia::render('Pp/WeeklyReport/Index', [
            'reports' => $reports,
        ]);
    }

    /**
     * Show create form.
     */
    public function create(Request $request)
    {

        $now = now();

        return Inertia::render('Pp/WeeklyReport/Create', [
            'defaults' => [
                'week_number' => (int) $now->format('W'),
                'year'        => (int) $now->format('Y'),
                'report_date' => $now->format('Y-m-d'),
            ],
        ]);
    }

    /**
     * Store a new weekly report with all sections and entries.
     */
    public function store(PpWeeklyReportRequest $request)
    {

        $validated = $request->validated();

        $report = DB::transaction(function () use ($validated, $request) {
            $report = PpWeeklyReport::create([
                'week_number' => $validated['week_number'],
                'year'        => $validated['year'],
                'report_date' => $validated['report_date'],
                'notes'       => $validated['notes'] ?? null,
                'user_id'     => $request->user()->id,
            ]);

            $this->saveSections($report, $validated['sections']);

            return $report;
        });

        AuditLog::log('create', 'PP weekly report created', $report);

        return redirect()->route('pp.weekly-reports.show', $report)
            ->with('success', 'Weekly report created successfully.');
    }

    /**
     * Show a single report (accessible to all authenticated users).
     */
    public function show(PpWeeklyReport $weeklyReport)
    {
        $weeklyReport->load([
            'author:id,name',
            'sections.projectEntries',
            'sections.netMeteringEntries',
        ]);

        return Inertia::render('Pp/WeeklyReport/Show', [
            'report' => $weeklyReport,
        ]);
    }

    /**
     * Show a single report in read-only mode (public viewer route).
     */
    public function publicShow(PpWeeklyReport $weeklyReport)
    {
        $weeklyReport->load([
            'author:id,name',
            'sections.projectEntries',
            'sections.netMeteringEntries',
        ]);

        return Inertia::render('Pp/WeeklyReport/Show', [
            'report'   => $weeklyReport,
            'readOnly' => true,
        ]);
    }

    /**
     * Show edit form (PP access only).
     */
    public function edit(Request $request, PpWeeklyReport $weeklyReport)
    {

        $weeklyReport->load([
            'author:id,name',
            'sections.projectEntries',
            'sections.netMeteringEntries',
        ]);

        return Inertia::render('Pp/WeeklyReport/Edit', [
            'report' => $weeklyReport,
        ]);
    }

    /**
     * Update a weekly report.
     */
    public function update(PpWeeklyReportRequest $request, PpWeeklyReport $weeklyReport)
    {

        $validated = $request->validated();
        $old = $weeklyReport->toArray();

        DB::transaction(function () use ($weeklyReport, $validated) {
            $weeklyReport->update([
                'week_number' => $validated['week_number'],
                'year'        => $validated['year'],
                'report_date' => $validated['report_date'],
                'notes'       => $validated['notes'] ?? null,
            ]);

            // Delete old sections and recreate
            $weeklyReport->sections()->delete();
            $this->saveSections($weeklyReport, $validated['sections']);
        });

        AuditLog::log('update', 'PP weekly report updated', $weeklyReport, $old, $weeklyReport->fresh()->toArray());

        return redirect()->route('pp.weekly-reports.show', $weeklyReport)
            ->with('success', 'Weekly report updated successfully.');
    }

    /**
     * Delete a weekly report (PP access only).
     */
    public function destroy(Request $request, PpWeeklyReport $weeklyReport)
    {

        AuditLog::log('delete', 'PP weekly report deleted', $weeklyReport);
        $weeklyReport->delete();

        return redirect()->route('pp.weekly-reports.index')
            ->with('success', 'Weekly report deleted successfully.');
    }

    /**
     * Save sections with their entries.
     */
    private function saveSections(PpWeeklyReport $report, array $sections): void
    {
        foreach ($sections as $i => $sectionData) {
            $section = $report->sections()->create([
                'section_number' => $i + 1,
                'title'          => $sectionData['title'],
                'section_type'   => $sectionData['section_type'],
                'sort_order'     => $sectionData['sort_order'],
            ]);

            if ($sectionData['section_type'] === 'net_metering') {
                foreach (($sectionData['net_metering_entries'] ?? []) as $entry) {
                    $section->netMeteringEntries()->create($entry);
                }
            } else {
                foreach (($sectionData['project_entries'] ?? []) as $entry) {
                    $section->projectEntries()->create($entry);
                }
            }
        }
    }
}
