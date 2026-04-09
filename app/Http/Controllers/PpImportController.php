<?php

namespace App\Http\Controllers;

use App\Imports\PpImport;
use App\Models\AuditLog;
use App\Models\Directorate;
use App\Models\PpProject;
use App\Models\PpMilestone;
use App\Models\PpFinancial;
use App\Models\PpRisk;
use App\Models\PpSafeguard;
use App\Models\PpProgrammeOutput;
use App\Models\PpGridImpactStudy;
use App\Models\PpWorkstream;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class PpImportController extends Controller
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
            abort(403, 'You do not have permission to import PP data.');
        }
    }

    /**
     * Parse an uploaded file and return a preview.
     */
    public function parseFile(Request $request)
    {
        $this->enforcePpAccess($request);

        $request->validate([
            'file'   => ['required', 'file', 'max:10240', 'mimes:xlsx,csv,xls'],
            'entity' => 'required|string|in:projects,milestones,financials,risks,safeguards,programme_outputs,grid_studies,workstreams',
        ]);

        $entity = $request->input('entity');

        $import = new PpImport();
        Excel::import($import, $request->file('file'));

        $rows = $import->getRows();
        $headers = $import->getHeaders();
        $autoMap = $import->autoMapHeaders($entity);

        $mappedData = [];
        if (!empty($autoMap)) {
            $mappedData = $import->mapToEntityData($autoMap);
        }

        session(["pp_import_{$entity}_data" => $rows]);
        session(["pp_import_{$entity}_headers" => $headers]);

        return response()->json([
            'success'          => true,
            'headers'          => $headers,
            'auto_mapping'     => $autoMap,
            'preview'          => array_slice($mappedData ?: $rows, 0, 50),
            'rows'             => $mappedData ?: $rows,
            'total_rows'       => count($rows),
            'mapped_fields'    => array_values($autoMap),
            'available_fields' => PpImport::availableFields($entity),
        ]);
    }

    /**
     * Confirm and import records.
     */
    public function confirmImport(Request $request)
    {
        $this->enforcePpAccess($request);

        $request->validate([
            'entity' => 'required|string|in:projects,milestones,financials,risks,safeguards,programme_outputs,grid_studies,workstreams',
            'rows'   => 'required|array|min:1',
        ]);

        $entity   = $request->input('entity');
        $rows     = $request->input('rows');
        $imported = 0;
        $skipped  = 0;
        $errors   = [];

        foreach ($rows as $index => $row) {
            try {
                $result = match ($entity) {
                    'projects'           => $this->upsertProject($row),
                    'milestones'         => $this->upsertMilestone($row),
                    'financials'         => $this->upsertFinancial($row),
                    'risks'              => $this->upsertRisk($row),
                    'safeguards'         => $this->upsertSafeguard($row),
                    'programme_outputs'  => $this->upsertProgrammeOutput($row),
                    'grid_studies'       => $this->upsertGridStudy($row),
                    'workstreams'        => $this->upsertWorkstream($row),
                };

                if ($result) {
                    $imported++;
                } else {
                    $skipped++;
                }
            } catch (\Exception $e) {
                $errors[] = "Row " . ($index + 1) . ": {$e->getMessage()}";
            }
        }

        AuditLog::log('create', "{$imported} PP {$entity} imported via file upload" . ($skipped ? ", {$skipped} skipped" : ''));

        session()->forget(["pp_import_{$entity}_data", "pp_import_{$entity}_headers"]);

        return response()->json([
            'success'  => true,
            'imported' => $imported,
            'skipped'  => $skipped,
            'errors'   => $errors,
            'message'  => "{$imported} {$entity} imported successfully." . ($skipped ? " {$skipped} skipped." : ''),
        ]);
    }

    /**
     * Download a CSV template for the given entity.
     */
    public function downloadTemplate(string $entity)
    {
        $fields = PpImport::availableFields($entity);
        if (empty($fields)) {
            abort(404, 'Unknown entity.');
        }

        $callback = function () use ($fields) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $fields);
            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"pp-{$entity}-template.csv\"",
        ]);
    }

    // ── Upsert helpers ─────────────────────────────────────

    private function cleanNumeric($value): ?float
    {
        if ($value === null || $value === '' || $value === '-') return null;
        $cleaned = preg_replace('/[^0-9.\-]/', '', (string) $value);
        return $cleaned !== '' ? (float) $cleaned : null;
    }

    private function cleanInt($value): ?int
    {
        if ($value === null || $value === '' || $value === '-') return null;
        $cleaned = preg_replace('/[^0-9\-]/', '', (string) $value);
        return $cleaned !== '' ? (int) $cleaned : null;
    }

    private function cleanDate($value): ?string
    {
        if ($value === null || $value === '' || $value === '-') return null;
        try {
            return \Carbon\Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    private function cleanBool($value): bool
    {
        if (is_bool($value)) return $value;
        $str = strtolower(trim((string) $value));
        return in_array($str, ['true', '1', 'yes', 'y'], true);
    }

    private function resolveProjectId(?string $code): ?int
    {
        if (!$code) return null;
        return PpProject::where('project_code', trim($code))->value('id');
    }

    private function resolveProjectIdFromRow(array $row): ?int
    {
        $projectId = $this->cleanInt($row['pp_project_id'] ?? null);
        if ($projectId) {
            $exists = PpProject::whereKey($projectId)->exists();
            if ($exists) {
                return $projectId;
            }
        }

        $projectCode = trim((string) ($row['_project_code'] ?? $row['project_code'] ?? ''));
        if ($projectCode !== '') {
            $resolved = $this->resolveProjectId($projectCode);
            if ($resolved) {
                return $resolved;
            }
        }

        $projectName = trim((string) ($row['_project_name'] ?? $row['project_name'] ?? ''));
        if ($projectName !== '') {
            return PpProject::where('project_name', $projectName)->value('id');
        }

        return null;
    }

    private function upsertProject(array $row): bool
    {
        $code = trim($row['project_code'] ?? '');
        if (!$code) return false;

        // Skip sample/template projects
        if (in_array($code, ['SOL-001', 'TL-002', 'SUB-003'])) return false;

        PpProject::updateOrCreate(
            ['project_code' => $code],
            array_filter([
                'project_name'             => trim($row['project_name'] ?? '') ?: null,
                'sector'                   => trim($row['sector'] ?? '') ?: null,
                'sub_sector'               => trim($row['sub_sector'] ?? '') ?: null,
                'project_stage'            => trim($row['project_stage'] ?? '') ?: null,
                'status'                   => trim($row['status'] ?? '') ?: null,
                'programme'                => trim($row['programme'] ?? '') ?: null,
                'province'                 => trim($row['province'] ?? '') ?: null,
                'district'                 => trim($row['district'] ?? '') ?: null,
                'contractor'               => trim($row['contractor'] ?? '') ?: null,
                'developer'                => trim($row['developer'] ?? '') ?: null,
                'cost_usd'                 => $this->cleanNumeric($row['cost_usd'] ?? null),
                'cost_zmw'                 => $this->cleanNumeric($row['cost_zmw'] ?? null),
                'capacity_mw'              => $this->cleanNumeric($row['capacity_mw'] ?? null),
                'commissioned_mw'          => $this->cleanNumeric($row['commissioned_mw'] ?? null),
                'progress_pct'             => $this->cleanNumeric($row['progress_pct'] ?? null),
                'cod_planned'              => $this->cleanDate($row['cod_planned'] ?? null),
                'key_issue_summary'        => trim($row['key_issue_summary'] ?? '') ?: null,
                'last_update_date'         => $this->cleanDate($row['last_update_date'] ?? null),
                'notes'                    => trim($row['notes'] ?? '') ?: null,
                'energy_type'              => trim($row['energy_type'] ?? '') ?: null,
                'renewable_flag'           => $this->cleanBool($row['renewable_flag'] ?? false),
                'market_segment'           => trim($row['market_segment'] ?? '') ?: null,
                'ownership_model'          => trim($row['ownership_model'] ?? '') ?: null,
                'owner_group'              => trim($row['owner_group'] ?? '') ?: null,
                'owner_entity'             => trim($row['owner_entity'] ?? '') ?: null,
                'is_ipp'                   => $this->cleanBool($row['is_ipp'] ?? false),
                'commissioned_mw_to_date'  => $this->cleanNumeric($row['commissioned_mw_to_date'] ?? null),
                'grid_connected'           => $this->cleanBool($row['grid_connected'] ?? false),
                'cod_actual'               => $this->cleanDate($row['cod_actual'] ?? null),
                'commissioned_date'        => $this->cleanDate($row['commissioned_date'] ?? null),
                'owner_subsidiary_name'    => trim($row['owner_subsidiary_name'] ?? '') ?: null,
                'owner_subsidiary_flag'    => $this->cleanBool($row['owner_subsidiary_flag'] ?? false),
                'commissioned_capacity_mw' => $this->cleanNumeric($row['commissioned_capacity_mw'] ?? null),
                'lifecycle_phase'          => trim($row['lifecycle_phase'] ?? '') ?: null,
                'project_manager'          => trim($row['project_manager'] ?? '') ?: null,
                'planned_start'            => $this->cleanDate($row['planned_start'] ?? null),
                'planned_finish'           => $this->cleanDate($row['planned_finish'] ?? null),
                'forecast_finish'          => $this->cleanDate($row['forecast_finish'] ?? null),
                'approved_budget'          => $this->cleanNumeric($row['approved_budget'] ?? null),
                'committed_cost'           => $this->cleanNumeric($row['committed_cost'] ?? null),
                'actual_spend'             => $this->cleanNumeric($row['actual_spend'] ?? null),
                'forecast_at_completion'   => $this->cleanNumeric($row['forecast_at_completion'] ?? null),
                'next_decision'            => trim($row['next_decision'] ?? '') ?: null,
            ], fn($v) => $v !== null)
        );

        return true;
    }

    private function upsertMilestone(array $row): bool
    {
        $code = trim($row['milestone_code'] ?? '');
        if (!$code) return false;

        $projectId = $this->resolveProjectIdFromRow($row);

        PpMilestone::updateOrCreate(
            ['milestone_code' => $code],
            array_filter([
                'pp_project_id' => $projectId,
                'milestone'     => trim($row['milestone'] ?? '') ?: null,
                'category'      => trim($row['category'] ?? '') ?: null,
                'baseline_date' => $this->cleanDate($row['baseline_date'] ?? null),
                'forecast_date' => $this->cleanDate($row['forecast_date'] ?? null),
                'actual_date'   => $this->cleanDate($row['actual_date'] ?? null),
                'weight_pct'    => $this->cleanNumeric($row['weight_pct'] ?? null),
                'delay_days'    => $this->cleanInt($row['delay_days'] ?? null),
                'owner'         => trim($row['owner'] ?? '') ?: null,
                'status'        => trim($row['status'] ?? 'Pending') ?: 'Pending',
                'notes'         => trim($row['notes'] ?? '') ?: null,
            ], fn($v) => $v !== null)
        );

        return true;
    }

    private function upsertFinancial(array $row): bool
    {
        $code = trim($row['finance_code'] ?? '');
        if (!$code) return false;

        $projectId = $this->resolveProjectIdFromRow($row);

        PpFinancial::updateOrCreate(
            ['finance_code' => $code],
            array_filter([
                'pp_project_id'    => $projectId,
                'as_of_date'       => $this->cleanDate($row['as_of_date'] ?? null),
                'committed_amount' => $this->cleanNumeric($row['committed_amount'] ?? null),
                'paid_to_date'     => $this->cleanNumeric($row['paid_to_date'] ?? null),
                'currency'         => trim($row['currency'] ?? 'USD'),
                'notes'            => trim($row['notes'] ?? '') ?: null,
            ], fn($v) => $v !== null)
        );

        return true;
    }

    private function upsertRisk(array $row): bool
    {
        $code = trim($row['risk_code'] ?? '');
        if (!$code) return false;

        $projectId = $this->resolveProjectIdFromRow($row);

        $likelihood = $this->cleanInt($row['likelihood'] ?? null) ?? 1;
        $impact     = $this->cleanInt($row['impact'] ?? null) ?? 1;
        $severity   = $this->cleanInt($row['severity'] ?? null) ?? ($likelihood * $impact);

        PpRisk::updateOrCreate(
            ['risk_code' => $code],
            array_filter([
                'record_type'      => trim($row['record_type'] ?? 'Risk') ?: 'Risk',
                'pp_project_id'    => $projectId,
                'risk_category'    => trim($row['risk_category'] ?? '') ?: null,
                'risk_description' => trim($row['risk_description'] ?? '') ?: null,
                'likelihood'       => $likelihood,
                'impact'           => $impact,
                'severity'         => $severity,
                'risk_level'       => trim($row['risk_level'] ?? '') ?: null,
                'mitigation'       => trim($row['mitigation'] ?? '') ?: null,
                'owner'            => trim($row['owner'] ?? '') ?: null,
                'due_date'         => $this->cleanDate($row['due_date'] ?? null),
                'status'           => trim($row['status'] ?? 'Open') ?: 'Open',
                'created_date'     => $this->cleanDate($row['created_date'] ?? null),
                'days_open'        => $this->cleanInt($row['days_open'] ?? null),
                'notes'            => trim($row['notes'] ?? '') ?: null,
            ], fn($v) => $v !== null)
        );

        return true;
    }

    private function upsertSafeguard(array $row): bool
    {
        $code = trim($row['record_code'] ?? '');
        if (!$code) return false;

        PpSafeguard::updateOrCreate(
            ['record_code' => $code],
            array_filter([
                'scope'             => trim($row['scope'] ?? '') ?: null,
                'pp_project_id'     => $this->resolveProjectIdFromRow($row),
                'wayleave_received' => $this->cleanInt($row['wayleave_received'] ?? null),
                'wayleave_cleared'  => $this->cleanInt($row['wayleave_cleared'] ?? null),
                'wayleave_pending'  => $this->cleanInt($row['wayleave_pending'] ?? null),
                'survey_received'   => $this->cleanInt($row['survey_received'] ?? null),
                'survey_cleared'    => $this->cleanInt($row['survey_cleared'] ?? null),
                'survey_pending'    => $this->cleanInt($row['survey_pending'] ?? null),
                'paps'              => $this->cleanInt($row['paps'] ?? null),
                'comp_paid_zmw'     => $this->cleanNumeric($row['comp_paid_zmw'] ?? null),
                'notes'             => trim($row['notes'] ?? '') ?: null,
            ], fn($v) => $v !== null)
        );

        return true;
    }

    private function upsertProgrammeOutput(array $row): bool
    {
        $code = trim($row['output_code'] ?? '');
        if (!$code) return false;

        PpProgrammeOutput::updateOrCreate(
            ['output_code' => $code],
            array_filter([
                'programme'               => trim($row['programme'] ?? '') ?: null,
                'period'                  => trim($row['period'] ?? '') ?: null,
                'connections_delivered'    => $this->cleanInt($row['connections_delivered'] ?? null),
                'transformers_energised'  => $this->cleanInt($row['transformers_energised'] ?? null),
                'jobs_pending_connection' => $this->cleanInt($row['jobs_pending_connection'] ?? null),
                'notes'                   => trim($row['notes'] ?? '') ?: null,
            ], fn($v) => $v !== null)
        );

        return true;
    }

    private function upsertGridStudy(array $row): bool
    {
        $code = trim($row['study_code'] ?? '');
        if (!$code) return false;

        PpGridImpactStudy::updateOrCreate(
            ['study_code' => $code],
            array_filter([
                'pp_project_id'        => $this->resolveProjectIdFromRow($row),
                'study_type'           => trim($row['study_type'] ?? '') ?: null,
                'name'                 => trim($row['name'] ?? '') ?: null,
                'capacity_mw'          => $this->cleanNumeric($row['capacity_mw'] ?? null),
                'developer'            => trim($row['developer'] ?? '') ?: null,
                'technology'           => trim($row['technology'] ?? '') ?: null,
                'project_area'         => trim($row['project_area'] ?? '') ?: null,
                'point_of_connection'  => trim($row['point_of_connection'] ?? '') ?: null,
                'progress_pct'         => $this->cleanNumeric($row['progress_pct'] ?? null),
                'stage_received'       => $this->cleanBool($row['stage_received'] ?? false),
                'stage_not_started'    => $this->cleanBool($row['stage_not_started'] ?? false),
                'stage_under_review'   => $this->cleanBool($row['stage_under_review'] ?? false),
                'stage_pending_client' => $this->cleanBool($row['stage_pending_client'] ?? false),
                'stage_revisions'      => $this->cleanBool($row['stage_revisions'] ?? false),
                'stage_approved'       => $this->cleanBool($row['stage_approved'] ?? false),
                'notes'                => trim($row['notes'] ?? '') ?: null,
            ], fn($v) => $v !== null)
        );

        return true;
    }

    private function upsertWorkstream(array $row): bool
    {
        $code = trim($row['workstream_code'] ?? '');
        if (!$code) return false;

        $projectId = $this->resolveProjectIdFromRow($row);
        if (!$projectId) return false;

        PpWorkstream::updateOrCreate(
            ['workstream_code' => $code],
            array_filter([
                'pp_project_id' => $projectId,
                'workstream'    => trim($row['workstream'] ?? '') ?: null,
                'planned_pct'   => $this->cleanNumeric($row['planned_pct'] ?? null),
                'actual_pct'    => $this->cleanNumeric($row['actual_pct'] ?? null),
                'variance_pct'  => $this->cleanNumeric($row['variance_pct'] ?? null),
                'status'        => trim($row['status'] ?? 'On Track') ?: 'On Track',
                'comments'      => trim($row['comments'] ?? '') ?: null,
            ], fn($v) => $v !== null)
        );

        return true;
    }
}
