<?php

namespace Database\Seeders;

use App\Models\PpProject;
use App\Models\PpMilestone;
use App\Models\PpFinancial;
use App\Models\PpRisk;
use App\Models\PpSafeguard;
use App\Models\PpProgrammeOutput;
use App\Models\PpGridImpactStudy;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class PpDataSeeder extends Seeder
{
    private string $csvDir;

    public function run(): void
    {
        $this->csvDir = storage_path('app/pp_data');

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

        $this->command?->info('PP data seeding complete!');
    }

    private function readCsv(string $filename): array
    {
        $path = $this->csvDir . DIRECTORY_SEPARATOR . $filename;

        if (!file_exists($path)) {
            $this->command?->warn("CSV file not found: {$filename}");
            return [];
        }

        $handle = fopen($path, 'r');
        if (!$handle) return [];

        // Read header row
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
                // Try to handle mismatched column counts
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
        return (float) str_replace([',', ' '], '', $value);
    }

    private function cleanInt(?string $value): ?int
    {
        if ($value === null || $value === '' || $value === '-') return null;
        return (int) str_replace([',', ' '], '', $value);
    }

    private function cleanDate(?string $value): ?string
    {
        if ($value === null || $value === '' || $value === '-') return null;
        try {
            return \Carbon\Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    private function seedProjects(): void
    {
        $rows = $this->readCsv('Projects.csv');
        $count = 0;

        foreach ($rows as $row) {
            $projectCode = trim($row['Project_ID'] ?? '');
            if (!$projectCode) continue;

            PpProject::updateOrCreate(
                ['project_code' => $projectCode],
                [
                    'project_name' => trim($row['Project_Name'] ?? ''),
                    'sector' => trim($row['Sector'] ?? ''),
                    'sub_sector' => trim($row['Sub_Sector'] ?? '') ?: null,
                    'status' => trim($row['Status'] ?? 'Preparation'),
                    'programme' => trim($row['Programme'] ?? '') ?: null,
                    'province' => trim($row['Province'] ?? '') ?: null,
                    'district' => trim($row['District'] ?? '') ?: null,
                    'contractor' => trim($row['Contractor'] ?? '') ?: null,
                    'developer' => trim($row['Developer'] ?? '') ?: null,
                    'funder' => trim($row['Funder'] ?? '') ?: null,
                    'funding_type' => trim($row['Funding_Type'] ?? '') ?: null,
                    'cost_usd' => $this->cleanNumeric($row['Cost_USD'] ?? null),
                    'cost_zmw' => $this->cleanNumeric($row['Cost_ZMW'] ?? null),
                    'capacity_mw' => $this->cleanNumeric($row['Capacity_MW'] ?? null),
                    'progress_pct' => $this->cleanNumeric($row['Progress_Pct'] ?? null),
                    'cod_planned' => $this->cleanDate($row['COD_Planned'] ?? null),
                    'key_issue_summary' => trim($row['Key_Issue_Summary'] ?? '') ?: null,
                    'last_update_date' => $this->cleanDate($row['Last_Update_Date'] ?? null),
                    'rag_status' => trim($row['RAG_Status'] ?? 'Amber'),
                ]
            );
            $count++;
        }

        $this->command?->info("  → {$count} projects seeded");
    }

    private function seedMilestones(): void
    {
        $rows = $this->readCsv('Milestones.csv');
        $count = 0;

        foreach ($rows as $row) {
            $code = trim($row['Milestone_ID'] ?? '');
            $projectCode = trim($row['Project_ID'] ?? '');
            if (!$code) continue;

            $project = PpProject::where('project_code', $projectCode)->first();

            PpMilestone::updateOrCreate(
                ['milestone_code' => $code],
                [
                    'pp_project_id' => $project?->id,
                    'milestone' => trim($row['Milestone'] ?? ''),
                    'actual_date' => $this->cleanDate($row['Actual_Date'] ?? null),
                    'status' => trim($row['Status'] ?? 'Pending'),
                    'notes' => trim($row['Notes'] ?? '') ?: null,
                ]
            );
            $count++;
        }

        $this->command?->info("  → {$count} milestones seeded");
    }

    private function seedFinancials(): void
    {
        $rows = $this->readCsv('Financials.csv');
        $count = 0;

        foreach ($rows as $row) {
            $code = trim($row['Finance_ID'] ?? '');
            if (!$code) continue;

            $projectCode = trim($row['Project_ID'] ?? '');
            $project = PpProject::where('project_code', $projectCode)->first();

            PpFinancial::updateOrCreate(
                ['finance_code' => $code],
                [
                    'pp_project_id' => $project?->id,
                    'as_of_date' => $this->cleanDate($row['As_Of_Date'] ?? null),
                    'committed_amount' => $this->cleanNumeric($row['Committed_Amount'] ?? null),
                    'paid_to_date' => $this->cleanNumeric($row['Paid_To_Date'] ?? null),
                    'currency' => trim($row['Currency'] ?? 'USD'),
                    'notes' => trim($row['Notes'] ?? '') ?: null,
                ]
            );
            $count++;
        }

        $this->command?->info("  → {$count} financials seeded");
    }

    private function seedRisks(): void
    {
        $rows = $this->readCsv('Risks.csv');
        $count = 0;

        foreach ($rows as $row) {
            $code = trim($row['Risk_ID'] ?? '');
            if (!$code) continue;

            $projectCode = trim($row['Project_ID'] ?? '');
            $project = PpProject::where('project_code', $projectCode)->first();

            $likelihood = $this->cleanInt($row['Likelihood_1_5'] ?? null) ?? 1;
            $impact = $this->cleanInt($row['Impact_1_5'] ?? null) ?? 1;
            // CSV has "Sevverity" (typo in header)
            $severity = $this->cleanInt($row['Sevverity'] ?? $row['Severity'] ?? null) ?? ($likelihood * $impact);

            PpRisk::updateOrCreate(
                ['risk_code' => $code],
                [
                    'pp_project_id' => $project?->id,
                    'risk_category' => trim($row['Risk_Category'] ?? '') ?: null,
                    'risk_description' => trim($row['Risk_Description'] ?? '') ?: null,
                    'likelihood' => $likelihood,
                    'impact' => $impact,
                    'severity' => $severity,
                    'risk_level' => trim($row['Risk_Level'] ?? '') ?: null,
                    'mitigation' => trim($row['Mitigation'] ?? '') ?: null,
                    'owner' => trim($row['Owner'] ?? '') ?: null,
                    'due_date' => $this->cleanDate($row['Due_Date'] ?? null),
                    'status' => trim($row['Status'] ?? 'Open'),
                ]
            );
            $count++;
        }

        $this->command?->info("  → {$count} risks seeded");
    }

    private function seedSafeguards(): void
    {
        $rows = $this->readCsv('Safeguards.csv');
        $count = 0;

        foreach ($rows as $row) {
            $code = trim($row['Record_ID'] ?? '');
            if (!$code) continue;

            // Try to find project from Scope field
            $scope = trim($row['Scope'] ?? '');
            $project = null;

            PpSafeguard::updateOrCreate(
                ['record_code' => $code],
                [
                    'scope' => $scope,
                    'pp_project_id' => $project?->id,
                    'wayleave_received' => $this->cleanInt($row['Wayleave_Received'] ?? null) ?? 0,
                    'wayleave_cleared' => $this->cleanInt($row['Wayleave_Cleared'] ?? null) ?? 0,
                    'wayleave_pending' => $this->cleanInt($row['Wayleave_Pending'] ?? null) ?? 0,
                    'survey_received' => $this->cleanInt($row['Survey_Received'] ?? null) ?? 0,
                    'survey_cleared' => $this->cleanInt($row['Survey_Cleared'] ?? null) ?? 0,
                    'survey_pending' => $this->cleanInt($row['Survey_Pending'] ?? null) ?? 0,
                    'paps' => $this->cleanInt($row['PAPs'] ?? null) ?? 0,
                    'comp_paid_zmw' => $this->cleanNumeric($row['Comp_Paid_ZMW'] ?? null),
                    'notes' => trim($row['Notes'] ?? '') ?: null,
                ]
            );
            $count++;
        }

        $this->command?->info("  → {$count} safeguards seeded");
    }

    private function seedProgrammeOutputs(): void
    {
        $rows = $this->readCsv('Programme_Outputs.csv');
        $count = 0;

        foreach ($rows as $row) {
            $code = trim($row['Programme_Output_ID'] ?? '');
            if (!$code) continue;

            PpProgrammeOutput::updateOrCreate(
                ['output_code' => $code],
                [
                    'programme' => trim($row['Programme'] ?? ''),
                    'period' => trim($row['Period'] ?? ''),
                    'connections_delivered' => $this->cleanInt($row['Connections_Delivered'] ?? null) ?? 0,
                    'transformers_energised' => $this->cleanInt($row['Transformers_Energised'] ?? null) ?? 0,
                    'jobs_pending_connection' => $this->cleanInt($row['Jobs_Pending_Connection'] ?? null) ?? 0,
                    'notes' => trim($row['Notes'] ?? '') ?: null,
                ]
            );
            $count++;
        }

        $this->command?->info("  → {$count} programme outputs seeded");
    }

    private function seedGridImpactStudies(): void
    {
        $rows = $this->readCsv('GridImpactStudies.csv');
        $count = 0;

        foreach ($rows as $row) {
            $studyCode = trim($row['Study_Code'] ?? '');
            if (!$studyCode) continue;

            $projectCode = trim($row['Project_ID'] ?? '');
            $project = $projectCode ? PpProject::where('project_code', $projectCode)->first() : null;

            $cleanBool = fn(?string $v): bool => in_array(strtolower(trim($v ?? '')), ['1', 'true', 'yes', 'y'], true);

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
                    'stage_received'       => $cleanBool($row['Stage_Received'] ?? null),
                    'stage_not_started'    => $cleanBool($row['Stage_Not_Started'] ?? null),
                    'stage_under_review'   => $cleanBool($row['Stage_Under_Review'] ?? null),
                    'stage_pending_client' => $cleanBool($row['Stage_Pending_Client'] ?? null),
                    'stage_revisions'      => $cleanBool($row['Stage_Revisions'] ?? null),
                    'stage_approved'       => $cleanBool($row['Stage_Approved'] ?? null),
                ]
            );
            $count++;
        }

        $this->command?->info("  → {$count} grid impact studies seeded");
    }
}
