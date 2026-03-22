<?php

namespace App\Console\Commands;

use App\Models\PpFinancial;
use App\Models\PpGridImpactStudy;
use App\Models\PpMilestone;
use App\Models\PpProgrammeOutput;
use App\Models\PpProject;
use App\Models\PpRisk;
use App\Models\PpSafeguard;
use App\Models\PpWorkstream;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ExportPpDataCommand extends Command
{
    protected $signature = 'pp:export {--path=storage/app/pp_exports : Output directory for CSV files}';

    protected $description = 'Export all PP data as CSV files importable via PpImportModal';

    public function handle(): int
    {
        $outputDir = base_path($this->option('path'));
        File::ensureDirectoryExists($outputDir);

        $this->exportProjects($outputDir);
        $this->exportMilestones($outputDir);
        $this->exportFinancials($outputDir);
        $this->exportRisks($outputDir);
        $this->exportSafeguards($outputDir);
        $this->exportProgrammeOutputs($outputDir);
        $this->exportGridImpactStudies($outputDir);
        $this->exportWorkstreams($outputDir);

        $this->info("PP data exported to: {$outputDir}");

        return self::SUCCESS;
    }

    private function exportProjects(string $dir): void
    {
        $headers = [
            'project_code', 'project_name', 'sector', 'sub_sector', 'project_stage', 'status',
            'programme', 'province', 'district', 'contractor', 'developer',
            'cost_usd', 'cost_zmw', 'capacity_mw', 'commissioned_mw', 'progress_pct',
            'cod_planned', 'key_issue_summary', 'last_update_date', 'notes',
            'energy_type', 'renewable_flag', 'market_segment', 'ownership_model',
            'owner_group', 'owner_entity', 'is_ipp', 'commissioned_mw_to_date',
            'grid_connected', 'cod_actual', 'commissioned_date', 'owner_subsidiary_name',
            'owner_subsidiary_flag', 'commissioned_capacity_mw', 'lifecycle_phase',
            'project_manager', 'planned_start', 'planned_finish', 'forecast_finish',
            'approved_budget', 'committed_cost', 'actual_spend', 'forecast_at_completion',
            'next_decision',
        ];

        $rows = PpProject::all()->map(fn ($p) => $this->pick($p, $headers))->toArray();
        $this->writeCsv($dir . '/projects.csv', $headers, $rows);
        $this->info("  → " . count($rows) . " projects");
    }

    private function exportMilestones(string $dir): void
    {
        $headers = [
            'milestone_code', 'project_code', 'milestone', 'category',
            'baseline_date', 'forecast_date', 'actual_date',
            'weight_pct', 'delay_days', 'owner', 'status', 'notes',
        ];

        $rows = PpMilestone::with('project')->get()->map(function ($m) use ($headers) {
            $row = $this->pick($m, $headers);
            $row['project_code'] = $m->project?->project_code;
            return $row;
        })->toArray();

        $this->writeCsv($dir . '/milestones.csv', $headers, $rows);
        $this->info("  → " . count($rows) . " milestones");
    }

    private function exportFinancials(string $dir): void
    {
        $headers = [
            'finance_code', 'project_code', 'as_of_date',
            'committed_amount', 'paid_to_date', 'currency', 'notes',
        ];

        $rows = PpFinancial::with('project')->get()->map(function ($f) use ($headers) {
            $row = $this->pick($f, $headers);
            $row['project_code'] = $f->project?->project_code;
            return $row;
        })->toArray();

        $this->writeCsv($dir . '/financials.csv', $headers, $rows);
        $this->info("  → " . count($rows) . " financials");
    }

    private function exportRisks(string $dir): void
    {
        $headers = [
            'risk_code', 'record_type', 'project_code', 'risk_category', 'risk_description',
            'likelihood', 'impact', 'severity', 'risk_level', 'mitigation',
            'owner', 'due_date', 'status', 'created_date', 'days_open', 'notes',
        ];

        $rows = PpRisk::with('project')->get()->map(function ($r) use ($headers) {
            $row = $this->pick($r, $headers);
            $row['project_code'] = $r->project?->project_code;
            return $row;
        })->toArray();

        $this->writeCsv($dir . '/risks.csv', $headers, $rows);
        $this->info("  → " . count($rows) . " risks");
    }

    private function exportSafeguards(string $dir): void
    {
        $headers = [
            'record_code', 'scope', 'project_code',
            'wayleave_received', 'wayleave_cleared', 'wayleave_pending',
            'survey_received', 'survey_cleared', 'survey_pending',
            'paps', 'comp_paid_zmw', 'report_period', 'notes',
        ];

        $rows = PpSafeguard::with('project')->get()->map(function ($s) use ($headers) {
            $row = $this->pick($s, $headers);
            $row['project_code'] = $s->project?->project_code;
            return $row;
        })->toArray();

        $this->writeCsv($dir . '/safeguards.csv', $headers, $rows);
        $this->info("  → " . count($rows) . " safeguards");
    }

    private function exportProgrammeOutputs(string $dir): void
    {
        $headers = [
            'output_code', 'programme', 'period',
            'connections_delivered', 'transformers_energised',
            'jobs_pending_connection', 'notes',
        ];

        $rows = PpProgrammeOutput::all()->map(fn ($o) => $this->pick($o, $headers))->toArray();
        $this->writeCsv($dir . '/programme_outputs.csv', $headers, $rows);
        $this->info("  → " . count($rows) . " programme outputs");
    }

    private function exportGridImpactStudies(string $dir): void
    {
        $headers = [
            'study_code', 'project_code', 'study_type', 'name', 'capacity_mw',
            'developer', 'technology', 'project_area', 'point_of_connection',
            'progress_pct', 'stage_received', 'stage_not_started', 'stage_under_review',
            'stage_pending_client', 'stage_revisions', 'stage_approved', 'notes',
        ];

        $rows = PpGridImpactStudy::with('project')->get()->map(function ($g) use ($headers) {
            $row = $this->pick($g, $headers);
            $row['project_code'] = $g->project?->project_code;
            return $row;
        })->toArray();

        $this->writeCsv($dir . '/grid_impact_studies.csv', $headers, $rows);
        $this->info("  → " . count($rows) . " grid impact studies");
    }

    private function exportWorkstreams(string $dir): void
    {
        $headers = [
            'workstream_code', 'project_code', 'workstream',
            'planned_pct', 'actual_pct', 'variance_pct',
            'status', 'comments',
        ];

        $rows = PpWorkstream::with('project')->get()->map(function ($w) use ($headers) {
            $row = $this->pick($w, $headers);
            $row['project_code'] = $w->project?->project_code;
            return $row;
        })->toArray();

        $this->writeCsv($dir . '/workstreams.csv', $headers, $rows);
        $this->info("  → " . count($rows) . " workstreams");
    }

    /**
     * Pick only the specified keys from a model, formatting dates as Y-m-d strings.
     */
    private function pick($model, array $keys): array
    {
        $row = [];
        foreach ($keys as $key) {
            $val = $model->{$key} ?? null;
            if ($val instanceof \DateTimeInterface) {
                $val = $val->format('Y-m-d');
            }
            $row[$key] = $val;
        }
        return $row;
    }

    /**
     * Write an array of rows to a CSV file.
     */
    private function writeCsv(string $path, array $headers, array $rows): void
    {
        $handle = fopen($path, 'w');
        fputcsv($handle, $headers);
        foreach ($rows as $row) {
            $ordered = [];
            foreach ($headers as $h) {
                $ordered[] = $row[$h] ?? '';
            }
            fputcsv($handle, $ordered);
        }
        fclose($handle);
    }
}
