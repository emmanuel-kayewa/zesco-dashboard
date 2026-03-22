<?php

namespace Database\Seeders;

use App\Models\PpProject;
use App\Models\PpMilestone;
use App\Models\PpFinancial;
use App\Models\PpRisk;
use App\Models\PpSafeguard;
use App\Models\PpProgrammeOutput;
use App\Models\PpGridImpactStudy;
use App\Models\PpWorkstream;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class PpDataSeeder extends Seeder
{
    private string $remsDir;
    private string $pmoDir;
    private string $rootDir;

    private const EXCLUDED_PROJECTS = ['SOL-001', 'TL-002', 'SUB-003'];

    public function run(): void
    {
        $this->rootDir = storage_path('app/pp_data');
        $this->remsDir = storage_path('app/pp_data/data/REMs');
        $this->pmoDir  = storage_path('app/pp_data/data/Reporting_Dashboard');

        $this->command?->info('Seeding PP Projects...');
        $this->seedProjects();

        $this->command?->info('Seeding PP Milestones...');
        $this->seedMilestones();

        $this->command?->info('Seeding PP Financials...');
        $this->seedFinancials();

        $this->command?->info('Seeding PP Risks...');
        $this->seedRisks();

        $this->command?->info('Seeding PP Safeguards...');
        $this->seedSafeguards();

        $this->command?->info('Seeding PP Programme Outputs...');
        $this->seedProgrammeOutputs();

        $this->command?->info('Seeding PP Grid Impact Studies...');
        $this->seedGridImpactStudies();

        $this->command?->info('Seeding PP Workstreams...');
        $this->seedWorkstreams();

        $this->command?->info('PP data seeding complete!');
    }

    private function readCsv(string $absolutePath): array
    {
        if (!file_exists($absolutePath)) {
            $this->command?->warn("CSV file not found: {$absolutePath}");
            return [];
        }

        $handle = fopen($absolutePath, 'r');
        if (!$handle) return [];

        $headers = fgetcsv($handle);
        if (!$headers) {
            fclose($handle);
            return [];
        }

        // Clean BOM from first header
        $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);
        $headers = array_map('trim', $headers);

        $rows = [];
        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) !== count($headers)) {
                if (count($data) < count($headers)) {
                    $data = array_pad($data, count($headers), null);
                } else {
                    $data = array_slice($data, 0, count($headers));
                }
            }
            $row = array_combine($headers, $data);
            if ($row) $rows[] = $row;
        }

        fclose($handle);
        return $rows;
    }

    private function cleanNumeric(?string $value): ?float
    {
        if ($value === null || $value === '' || $value === '-') return null;
        // Strip currency symbols, commas, spaces, parentheses
        $cleaned = preg_replace('/[^0-9.\-]/', '', str_replace(['(', ')'], ['-', ''], $value));
        return $cleaned !== '' ? (float) $cleaned : null;
    }

    private function cleanInt(?string $value): ?int
    {
        if ($value === null || $value === '' || $value === '-') return null;
        $cleaned = preg_replace('/[^0-9\-]/', '', $value);
        return $cleaned !== '' ? (int) $cleaned : null;
    }

    private function cleanDate(?string $value): ?string
    {
        if ($value === null || $value === '' || $value === '-') return null;
        // Handle Excel serial date numbers (pure digits, typically 5 digits like 45657)
        if (preg_match('/^\d{4,6}$/', trim($value))) {
            $serial = (int) $value;
            // Excel epoch is 1899-12-30, with the Lotus 1-2-3 leap year bug (+1 for dates > 59)
            $days = $serial > 59 ? $serial - 2 : $serial - 1;
            $date = \Carbon\Carbon::createFromFormat('Y-m-d', '1899-12-30')->addDays($days);
            return $date->format('Y-m-d');
        }
        try {
            return \Carbon\Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    private function cleanBool(?string $value): bool
    {
        if ($value === null || $value === '') return false;
        return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'y'], true);
    }

    private function cleanPct(?string $value): ?float
    {
        if ($value === null || $value === '' || $value === '-') return null;
        // Strip % sign and parentheses for negative values
        $cleaned = str_replace(['%', '(', ')'], ['', '-', ''], trim($value));
        $cleaned = preg_replace('/[^0-9.\-]/', '', $cleaned);
        return $cleaned !== '' ? (float) $cleaned : null;
    }

    // ─── Projects ────────────────────────────────────────────────

    private function seedProjects(): void
    {
        // Primary source: REMs projects.csv (has all REMs classification columns)
        $remsRows = $this->readCsv($this->remsDir . DIRECTORY_SEPARATOR . 'projects.csv');

        // Secondary source: Reporting Dashboard projects.csv (has PMO columns)
        $pmoRows = $this->readCsv($this->pmoDir . DIRECTORY_SEPARATOR . 'projects.csv');

        // Index PMO rows by project name for merging
        $pmoByName = [];
        foreach ($pmoRows as $row) {
            $name = trim($row['Project Name'] ?? '');
            if ($name) $pmoByName[$name] = $row;
        }

        $count = 0;
        foreach ($remsRows as $row) {
            $projectCode = trim($row['Project_ID'] ?? '');
            if (!$projectCode) continue;
            if (in_array($projectCode, self::EXCLUDED_PROJECTS, true)) continue;

            $data = [
                'project_name'             => trim($row['Project_Name'] ?? ''),
                'sector'                   => trim($row['Sector'] ?? ''),
                'sub_sector'               => trim($row['Sub_Sector'] ?? '') ?: null,
                'project_stage'            => trim($row['Status'] ?? 'Preparation'),
                'programme'                => trim($row['Programme'] ?? '') ?: null,
                'province'                 => trim($row['Province'] ?? '') ?: null,
                'district'                 => trim($row['District'] ?? '') ?: null,
                'contractor'               => trim($row['Contractor'] ?? '') ?: null,
                'developer'                => trim($row['Developer'] ?? '') ?: null,
                'cost_usd'                 => $this->cleanNumeric($row['Cost_USD'] ?? null),
                'cost_zmw'                 => $this->cleanNumeric($row['Cost_ZMW'] ?? null),
                'capacity_mw'              => $this->cleanNumeric($row['Capacity_MW'] ?? null),
                'progress_pct'             => $this->cleanNumeric($row['Progress_Pct'] ?? null),
                'cod_planned'              => $this->cleanDate($row['COD_Planned'] ?? null),
                'key_issue_summary'        => trim($row['Key_Issue_Summary'] ?? '') ?: null,
                'last_update_date'         => $this->cleanDate($row['Last_Update_Date'] ?? null),
                // REMs classification columns
                'energy_type'              => trim($row['Energy_Type'] ?? '') ?: null,
                'renewable_flag'           => $this->cleanBool($row['Renewable_Flag'] ?? null),
                'market_segment'           => trim($row['Market_Segment'] ?? '') ?: null,
                'ownership_model'          => trim($row['Ownership_Model'] ?? '') ?: null,
                'owner_group'              => trim($row['Owner_Group'] ?? '') ?: null,
                'owner_entity'             => trim($row['Owner_Entity'] ?? '') ?: null,
                'is_ipp'                   => $this->cleanBool($row['Is_IPP'] ?? null),
                'commissioned_mw_to_date'  => $this->cleanNumeric($row['Commissioned_MW_to_Date'] ?? null),
                'grid_connected'           => $this->cleanBool($row['Grid_Connected'] ?? null),
                'cod_actual'               => $this->cleanDate($row['COD_Actual'] ?? null),
                'commissioned_date'        => $this->cleanDate($row['Commissioned_Date'] ?? null),
                'owner_subsidiary_name'    => trim($row['Owner_Subsidiary_Name'] ?? '') ?: null,
                'owner_subsidiary_flag'    => $this->cleanBool($row['Owner_Subsidiary_Flag'] ?? null),
                'commissioned_capacity_mw' => $this->cleanNumeric($row['Commissioned_Capacity_MW'] ?? null),
                'lifecycle_phase'          => trim($row['Lifecycle_Phase'] ?? '') ?: null,
            ];

            // Merge PMO data if a matching project name exists in the Reporting Dashboard
            $projectName = $data['project_name'];
            if (isset($pmoByName[$projectName])) {
                $pmo = $pmoByName[$projectName];
                $data['project_manager']       = trim($pmo['Project Manager'] ?? '') ?: null;
                $data['planned_start']         = $this->cleanDate($pmo['Planned Start'] ?? null);
                $data['planned_finish']        = $this->cleanDate($pmo['Planned Finish'] ?? null);
                $data['forecast_finish']       = $this->cleanDate($pmo['Forecast Finish'] ?? null);
                $data['approved_budget']       = $this->cleanNumeric($pmo['Approved Budget'] ?? null);
                $data['committed_cost']        = $this->cleanNumeric($pmo['Committed Cost'] ?? null);
                $data['actual_spend']          = $this->cleanNumeric($pmo['Actual Spend'] ?? null);
                $data['forecast_at_completion'] = $this->cleanNumeric($pmo['Forecast at Completion'] ?? null);
                $data['next_decision']         = trim($pmo['Next Decision'] ?? '') ?: null;
                // Health status from Reporting Dashboard (On Track / Delayed / At Risk)
                $healthStatus = trim($pmo['Status'] ?? '');
                if (in_array($healthStatus, ['On Track', 'Delayed', 'At Risk'], true)) {
                    $data['status'] = $healthStatus;
                }
                // Use PMO progress if REMs progress is empty
                if ($data['progress_pct'] === null) {
                    $data['progress_pct'] = $this->cleanPct($pmo['Actual Progress'] ?? null);
                }
                // Use PMO last update if REMs is empty
                if ($data['last_update_date'] === null) {
                    $data['last_update_date'] = $this->cleanDate($pmo['Last Update'] ?? null);
                }
            }

            PpProject::updateOrCreate(['project_code' => $projectCode], $data);
            $count++;
        }

        $this->command?->info("  → {$count} projects seeded");
    }

    // ─── Milestones ──────────────────────────────────────────────

    private function seedMilestones(): void
    {
        // Source 1: REMs milestones (basic: code, project, milestone, actual_date, status, notes)
        $remsRows = $this->readCsv($this->remsDir . DIRECTORY_SEPARATOR . 'milestones.csv');
        $count = 0;

        foreach ($remsRows as $row) {
            $code = trim($row['Milestone_ID'] ?? '');
            $projectCode = trim($row['Project_ID'] ?? '');
            if (!$code) continue;
            if (in_array($projectCode, self::EXCLUDED_PROJECTS, true)) continue;

            $project = PpProject::where('project_code', $projectCode)->first();

            PpMilestone::updateOrCreate(
                ['milestone_code' => $code],
                [
                    'pp_project_id' => $project?->id,
                    'milestone'     => trim($row['Milestone'] ?? ''),
                    'actual_date'   => $this->cleanDate($row['Actual_Date'] ?? null),
                    'status'        => trim($row['Status'] ?? 'Pending'),
                    'notes'         => trim($row['Notes'] ?? '') ?: null,
                ]
            );
            $count++;
        }

        // Source 2: Reporting Dashboard milestones (has Category, Baseline Date, Forecast Date, Weight %, etc.)
        // These use "Project Name" instead of Project_ID, so we look up by name
        $pmoRows = $this->readCsv($this->pmoDir . DIRECTORY_SEPARATOR . 'milestones.csv');
        foreach ($pmoRows as $i => $row) {
            $projectName = trim($row['Project Name'] ?? '');
            $milestone   = trim($row['Milestone'] ?? '');
            if (!$projectName || !$milestone) continue;

            $project = PpProject::where('project_name', $projectName)->first();
            if (!$project) continue;
            if (in_array($project->project_code, self::EXCLUDED_PROJECTS, true)) continue;

            // Generate a deterministic code from project + milestone index
            $code = 'MS-PMO-' . $project->project_code . '-' . ($i + 1);

            PpMilestone::updateOrCreate(
                ['milestone_code' => $code],
                [
                    'pp_project_id' => $project->id,
                    'milestone'     => $milestone,
                    'category'      => trim($row['Category'] ?? '') ?: null,
                    'baseline_date' => $this->cleanDate($row['Baseline Date'] ?? null),
                    'forecast_date' => $this->cleanDate($row['Forecast Date'] ?? null),
                    'actual_date'   => $this->cleanDate($row['Actual Date'] ?? null),
                    'status'        => trim($row['Status'] ?? 'Pending'),
                    'weight_pct'    => $this->cleanPct($row['Weight %'] ?? null),
                    'delay_days'    => $this->cleanInt($row['Delay Days'] ?? null),
                    'owner'         => trim($row['Owner'] ?? '') ?: null,
                    'notes'         => trim($row['Comments'] ?? '') ?: null,
                ]
            );
            $count++;
        }

        $this->command?->info("  → {$count} milestones seeded");
    }

    // ─── Financials ──────────────────────────────────────────────

    private function seedFinancials(): void
    {
        $rows = $this->readCsv($this->remsDir . DIRECTORY_SEPARATOR . 'financials.csv');
        $count = 0;

        foreach ($rows as $row) {
            $code = trim($row['Finance_ID'] ?? '');
            if (!$code) continue;

            $projectCode = trim($row['Project_ID'] ?? '');
            if (in_array($projectCode, self::EXCLUDED_PROJECTS, true)) continue;

            $project = PpProject::where('project_code', $projectCode)->first();

            PpFinancial::updateOrCreate(
                ['finance_code' => $code],
                [
                    'pp_project_id'    => $project?->id,
                    'as_of_date'       => $this->cleanDate($row['As_Of_Date'] ?? null),
                    'committed_amount' => $this->cleanNumeric($row['Committed_Amount'] ?? null),
                    'paid_to_date'     => $this->cleanNumeric($row['Paid_To_Date'] ?? null),
                    'currency'         => trim($row['Currency'] ?? 'USD'),
                    'notes'            => trim($row['Notes'] ?? '') ?: null,
                ]
            );
            $count++;
        }

        $this->command?->info("  → {$count} financials seeded");
    }

    // ─── Risks ───────────────────────────────────────────────────

    private function seedRisks(): void
    {
        // Source 1: REMs risks (standard risk register)
        $remsRows = $this->readCsv($this->remsDir . DIRECTORY_SEPARATOR . 'risks.csv');
        $count = 0;

        foreach ($remsRows as $row) {
            $code = trim($row['Risk_ID'] ?? '');
            if (!$code) continue;

            $projectCode = trim($row['Project_ID'] ?? '');
            if (in_array($projectCode, self::EXCLUDED_PROJECTS, true)) continue;

            $project = PpProject::where('project_code', $projectCode)->first();

            $likelihood = $this->cleanInt($row['Likelihood_1_5'] ?? null) ?? 1;
            $impact     = $this->cleanInt($row['Impact_1_5'] ?? null) ?? 1;
            $severity   = $this->cleanInt($row['Sevverity'] ?? $row['Severity'] ?? null) ?? ($likelihood * $impact);

            PpRisk::updateOrCreate(
                ['risk_code' => $code],
                [
                    'pp_project_id'   => $project?->id,
                    'record_type'     => 'Risk',
                    'risk_category'   => trim($row['Risk_Category'] ?? '') ?: null,
                    'risk_description' => trim($row['Risk_Description'] ?? '') ?: null,
                    'likelihood'      => $likelihood,
                    'impact'          => $impact,
                    'severity'        => $severity,
                    'risk_level'      => trim($row['Risk_Level'] ?? '') ?: null,
                    'mitigation'      => trim($row['Mitigation'] ?? '') ?: null,
                    'owner'           => trim($row['Owner'] ?? '') ?: null,
                    'due_date'        => $this->cleanDate($row['Due_Date'] ?? null),
                    'status'          => trim($row['Status'] ?? 'Open'),
                ]
            );
            $count++;
        }

        // Source 2: Reporting Dashboard risk_issues.csv (has Record Type, Created Date, Days Open)
        $pmoRows = $this->readCsv($this->pmoDir . DIRECTORY_SEPARATOR . 'risk_issues.csv');
        foreach ($pmoRows as $row) {
            $code = trim($row['Record ID'] ?? '');
            if (!$code) continue;

            $projectName = trim($row['Project Name'] ?? '');
            $project = $projectName ? PpProject::where('project_name', $projectName)->first() : null;
            if ($project && in_array($project->project_code, self::EXCLUDED_PROJECTS, true)) continue;

            $impact     = $this->cleanInt($row['Impact (1-5)'] ?? null) ?? 1;
            $likelihood = $this->cleanInt($row['Probability (1-5)'] ?? null) ?? 1;
            $score      = $this->cleanInt($row['Score'] ?? null) ?? ($likelihood * $impact);

            PpRisk::updateOrCreate(
                ['risk_code' => $code],
                [
                    'pp_project_id'    => $project?->id,
                    'record_type'      => trim($row['Record Type'] ?? 'Risk'),
                    'risk_category'    => trim($row['Category'] ?? '') ?: null,
                    'risk_description' => trim($row['Description'] ?? '') ?: null,
                    'likelihood'       => $likelihood,
                    'impact'           => $impact,
                    'severity'         => $score,
                    'risk_level'       => trim($row['Severity'] ?? '') ?: null,
                    'mitigation'       => trim($row['Mitigation Action'] ?? '') ?: null,
                    'owner'            => trim($row['Owner'] ?? '') ?: null,
                    'due_date'         => $this->cleanDate($row['Due Date'] ?? null),
                    'status'           => trim($row['Status'] ?? 'Open'),
                    'created_date'     => $this->cleanDate($row['Created Date'] ?? null),
                    'days_open'        => $this->cleanInt($row['Days Open'] ?? null),
                ]
            );
            $count++;
        }

        $this->command?->info("  → {$count} risks seeded");
    }

    // ─── Safeguards ──────────────────────────────────────────────

    private function seedSafeguards(): void
    {
        $rows = $this->readCsv($this->remsDir . DIRECTORY_SEPARATOR . 'safeguards.csv');
        $count = 0;

        foreach ($rows as $row) {
            $code = trim($row['Record_ID'] ?? '');
            if (!$code) continue;

            $scope   = trim($row['Scope'] ?? '');
            $project = null;

            PpSafeguard::updateOrCreate(
                ['record_code' => $code],
                [
                    'scope'             => $scope,
                    'pp_project_id'     => $project?->id,
                    'wayleave_received' => $this->cleanInt($row['Wayleave_Received'] ?? null) ?? 0,
                    'wayleave_cleared'  => $this->cleanInt($row['Wayleave_Cleared'] ?? null) ?? 0,
                    'wayleave_pending'  => $this->cleanInt($row['Wayleave_Pending'] ?? null) ?? 0,
                    'survey_received'   => $this->cleanInt($row['Survey_Received'] ?? null) ?? 0,
                    'survey_cleared'    => $this->cleanInt($row['Survey_Cleared'] ?? null) ?? 0,
                    'survey_pending'    => $this->cleanInt($row['Survey_Pending'] ?? null) ?? 0,
                    'paps'              => $this->cleanInt($row['PAPs'] ?? null) ?? 0,
                    'comp_paid_zmw'     => $this->cleanNumeric($row['Comp_Paid_ZMW'] ?? null),
                    'notes'             => trim($row['Notes'] ?? '') ?: null,
                ]
            );
            $count++;
        }

        $this->command?->info("  → {$count} safeguards seeded");
    }

    // ─── Programme Outputs ───────────────────────────────────────

    private function seedProgrammeOutputs(): void
    {
        $rows = $this->readCsv($this->remsDir . DIRECTORY_SEPARATOR . 'programme_outputs.csv');
        $count = 0;

        foreach ($rows as $row) {
            $code = trim($row['Programme_Output_ID'] ?? '');
            if (!$code) continue;

            PpProgrammeOutput::updateOrCreate(
                ['output_code' => $code],
                [
                    'programme'                => trim($row['Programme'] ?? ''),
                    'period'                   => trim($row['Period'] ?? ''),
                    'connections_delivered'     => $this->cleanInt($row['Connections_Delivered'] ?? null) ?? 0,
                    'transformers_energised'   => $this->cleanInt($row['Transformers_Energised'] ?? null) ?? 0,
                    'jobs_pending_connection'   => $this->cleanInt($row['Jobs_Pending_Connection'] ?? null) ?? 0,
                    'notes'                    => trim($row['Notes'] ?? '') ?: null,
                ]
            );
            $count++;
        }

        $this->command?->info("  → {$count} programme outputs seeded");
    }

    // ─── Grid Impact Studies ─────────────────────────────────────

    private function seedGridImpactStudies(): void
    {
        $rows = $this->readCsv($this->rootDir . DIRECTORY_SEPARATOR . 'GridImpactStudies.csv');
        $count = 0;

        foreach ($rows as $row) {
            $studyCode = trim($row['Study_Code'] ?? '');
            if (!$studyCode) continue;

            $projectCode = trim($row['Project_ID'] ?? '');
            $project = $projectCode ? PpProject::where('project_code', $projectCode)->first() : null;

            PpGridImpactStudy::updateOrCreate(
                ['study_code' => $studyCode],
                [
                    'pp_project_id'        => $project?->id,
                    'study_type'           => strtolower(trim($row['Study_Type'] ?? 'report')),
                    'name'                 => trim($row['Name'] ?? ''),
                    'capacity_mw'          => $this->cleanNumeric($row['Capacity_MW'] ?? null),
                    'developer'            => trim($row['Developer'] ?? '') ?: null,
                    'technology'           => trim($row['Technology'] ?? '') ?: null,
                    'project_area'         => trim($row['Project_Area'] ?? '') ?: null,
                    'point_of_connection'  => trim($row['Point_of_Connection'] ?? '') ?: null,
                    'progress_pct'         => $this->cleanNumeric($row['Progress_Pct'] ?? null),
                    'stage_received'       => $this->cleanBool($row['Stage_Received'] ?? null),
                    'stage_not_started'    => $this->cleanBool($row['Stage_Not_Started'] ?? null),
                    'stage_under_review'   => $this->cleanBool($row['Stage_Under_Review'] ?? null),
                    'stage_pending_client' => $this->cleanBool($row['Stage_Pending_Client'] ?? null),
                    'stage_revisions'      => $this->cleanBool($row['Stage_Revisions'] ?? null),
                    'stage_approved'       => $this->cleanBool($row['Stage_Approved'] ?? null),
                ]
            );
            $count++;
        }

        $this->command?->info("  → {$count} grid impact studies seeded");
    }

    // ─── Workstreams ─────────────────────────────────────────────

    private function seedWorkstreams(): void
    {
        $rows = $this->readCsv($this->pmoDir . DIRECTORY_SEPARATOR . 'workstreams.csv');
        $count = 0;

        foreach ($rows as $i => $row) {
            $projectName = trim($row['Project Name'] ?? '');
            $workstream  = trim($row['Workstream'] ?? '');
            if (!$projectName || !$workstream) continue;

            $project = PpProject::where('project_name', $projectName)->first();
            if (!$project) continue;
            if (in_array($project->project_code, self::EXCLUDED_PROJECTS, true)) continue;

            $code = 'WS-' . $project->project_code . '-' . strtoupper(substr($workstream, 0, 3));

            PpWorkstream::updateOrCreate(
                ['workstream_code' => $code],
                [
                    'pp_project_id' => $project->id,
                    'workstream'    => $workstream,
                    'planned_pct'   => $this->cleanPct($row['Planned %'] ?? null),
                    'actual_pct'    => $this->cleanPct($row['Actual %'] ?? null),
                    'variance_pct'  => $this->cleanPct($row['Variance'] ?? null),
                    'status'        => trim($row['Status'] ?? '') ?: null,
                    'comments'      => trim($row['Comments'] ?? '') ?: null,
                ]
            );
            $count++;
        }

        $this->command?->info("  → {$count} workstreams seeded");
    }
}
