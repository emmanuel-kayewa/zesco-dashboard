<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;

class PpImport implements ToArray, WithHeadingRow, WithCalculatedFormulas
{
    private array $rows = [];
    private array $headers = [];

    public function array(array $rows): void
    {
        $this->rows = $rows;

        if (!empty($rows)) {
            $this->headers = array_keys($rows[0]);
        }
    }

    public function getRows(): array
    {
        return $this->rows;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Auto-map file headers to model fields for a given entity.
     */
    public function autoMapHeaders(string $entity): array
    {
        $mappings = [];
        $headerMap = self::headerMaps()[$entity] ?? [];

        foreach ($this->headers as $header) {
            $normalised = strtolower(trim(str_replace(['-', '/', '\\', ' '], '_', $header)));
            if (isset($headerMap[$normalised])) {
                $mappings[$header] = $headerMap[$normalised];
            }
        }

        return $mappings;
    }

    /**
     * Transform parsed rows using the given column mapping.
     */
    public function mapToEntityData(array $columnMap): array
    {
        $reverseMap = array_flip($columnMap);
        $result = [];

        foreach ($this->rows as $row) {
            $mapped = [];
            foreach ($reverseMap as $modelField => $fileHeader) {
                $mapped[$modelField] = $row[$fileHeader] ?? null;
            }
            $result[] = $mapped;
        }

        return $result;
    }

    /**
     * Header synonym maps per entity.
     */
    public static function headerMaps(): array
    {
        return [
            'projects' => [
                'project_id'               => 'project_code',
                'project_code'             => 'project_code',
                'project id'               => 'project_code',
                'project_name'             => 'project_name',
                'project name'             => 'project_name',
                'name'                     => 'project_name',
                'sector'                   => 'sector',
                'sub_sector'               => 'sub_sector',
                'sub sector'               => 'sub_sector',
                'project_stage'            => 'project_stage',
                'project stage'            => 'project_stage',
                'stage'                    => 'project_stage',
                'status'                   => 'status',
                'health_status'            => 'status',
                'health status'            => 'status',
                'programme'                => 'programme',
                'program'                  => 'programme',
                'province'                 => 'province',
                'district'                 => 'district',
                'location'                 => 'district',
                'contractor'               => 'contractor',
                'developer'                => 'developer',
                'cost_usd'                 => 'cost_usd',
                'cost usd'                 => 'cost_usd',
                'approved_budget'          => 'approved_budget',
                'approved budget'          => 'approved_budget',
                'cost_zmw'                 => 'cost_zmw',
                'cost zmw'                 => 'cost_zmw',
                'capacity_mw'              => 'capacity_mw',
                'capacity (mw)'            => 'capacity_mw',
                'capacity mw'              => 'capacity_mw',
                'commissioned_mw'          => 'commissioned_mw',
                'commissioned mw'          => 'commissioned_mw',
                'progress_pct'             => 'progress_pct',
                'actual_progress'          => 'progress_pct',
                'actual progress'          => 'progress_pct',
                'cod_planned'              => 'cod_planned',
                'planned_finish'           => 'planned_finish',
                'planned finish'           => 'planned_finish',
                'forecast_finish'          => 'forecast_finish',
                'forecast finish'          => 'forecast_finish',
                'key_issue_summary'        => 'key_issue_summary',
                'key issue summary'        => 'key_issue_summary',
                'notes'                    => 'notes',
                'energy_type'              => 'energy_type',
                'energy type'              => 'energy_type',
                'renewable_flag'           => 'renewable_flag',
                'renewable flag'           => 'renewable_flag',
                'market_segment'           => 'market_segment',
                'market segment'           => 'market_segment',
                'ownership_model'          => 'ownership_model',
                'ownership model'          => 'ownership_model',
                'owner_group'              => 'owner_group',
                'owner group'              => 'owner_group',
                'owner_entity'             => 'owner_entity',
                'owner entity'             => 'owner_entity',
                'is_ipp'                   => 'is_ipp',
                'commissioned_mw_to_date'  => 'commissioned_mw_to_date',
                'grid_connected'           => 'grid_connected',
                'grid connected'           => 'grid_connected',
                'cod_actual'               => 'cod_actual',
                'commissioned_date'        => 'commissioned_date',
                'owner_subsidiary_name'    => 'owner_subsidiary_name',
                'owner_subsidiary_flag'    => 'owner_subsidiary_flag',
                'commissioned_capacity_mw' => 'commissioned_capacity_mw',
                'lifecycle_phase'          => 'lifecycle_phase',
                'lifecycle phase'          => 'lifecycle_phase',
                'project_manager'          => 'project_manager',
                'project manager'          => 'project_manager',
                'planned_start'            => 'planned_start',
                'planned start'            => 'planned_start',
                'committed_cost'           => 'committed_cost',
                'committed cost'           => 'committed_cost',
                'actual_spend'             => 'actual_spend',
                'actual spend'             => 'actual_spend',
                'forecast_at_completion'   => 'forecast_at_completion',
                'forecast at completion'   => 'forecast_at_completion',
                'next_decision'            => 'next_decision',
                'next decision'            => 'next_decision',
                'last_update_date'         => 'last_update_date',
                'last update'              => 'last_update_date',
            ],
            'milestones' => [
                'milestone_id'      => 'milestone_code',
                'milestone_code'    => 'milestone_code',
                'milestone id'      => 'milestone_code',
                'project_id'        => '_project_code',
                'project id'        => '_project_code',
                'project_name'      => '_project_name',
                'project name'      => '_project_name',
                'milestone'         => 'milestone',
                'category'          => 'category',
                'baseline_date'     => 'baseline_date',
                'baseline date'     => 'baseline_date',
                'forecast_date'     => 'forecast_date',
                'forecast date'     => 'forecast_date',
                'actual_date'       => 'actual_date',
                'actual date'       => 'actual_date',
                'weight_%'          => 'weight_pct',
                'weight_pct'        => 'weight_pct',
                'weight %'          => 'weight_pct',
                'delay_days'        => 'delay_days',
                'delay days'        => 'delay_days',
                'owner'             => 'owner',
                'status'            => 'status',
                'notes'             => 'notes',
                'comments'          => 'notes',
            ],
            'financials' => [
                'finance_id'       => 'finance_code',
                'finance_code'     => 'finance_code',
                'finance id'       => 'finance_code',
                'project_id'       => '_project_code',
                'project id'       => '_project_code',
                'as_of_date'       => 'as_of_date',
                'as of date'       => 'as_of_date',
                'committed_amount' => 'committed_amount',
                'committed amount' => 'committed_amount',
                'paid_to_date'     => 'paid_to_date',
                'paid to date'     => 'paid_to_date',
                'currency'         => 'currency',
                'notes'            => 'notes',
            ],
            'risks' => [
                'risk_id'          => 'risk_code',
                'risk_code'        => 'risk_code',
                'risk id'          => 'risk_code',
                'record_id'        => 'risk_code',
                'record id'        => 'risk_code',
                'record_type'      => 'record_type',
                'record type'      => 'record_type',
                'project_id'       => '_project_code',
                'project id'       => '_project_code',
                'project_name'     => '_project_name',
                'project name'     => '_project_name',
                'risk_category'    => 'risk_category',
                'risk category'    => 'risk_category',
                'category'         => 'risk_category',
                'risk_description' => 'risk_description',
                'description'      => 'risk_description',
                'likelihood_1_5'   => 'likelihood',
                'likelihood'       => 'likelihood',
                'impact_1_5'       => 'impact',
                'impact (1_5)'     => 'impact',
                'impact'           => 'impact',
                'probability (1_5)' => 'likelihood',
                'severity'         => 'severity',
                'sevverity'        => 'severity',
                'score'            => 'severity',
                'risk_level'       => 'risk_level',
                'risk level'       => 'risk_level',
                'mitigation'       => 'mitigation',
                'mitigation_action' => 'mitigation',
                'mitigation action' => 'mitigation',
                'owner'            => 'owner',
                'due_date'         => 'due_date',
                'due date'         => 'due_date',
                'status'           => 'status',
                'created_date'     => 'created_date',
                'created date'     => 'created_date',
                'days_open'        => 'days_open',
                'days open'        => 'days_open',
                'notes'            => 'notes',
            ],
            'safeguards' => [
                'record_id'         => 'record_code',
                'record_code'       => 'record_code',
                'record id'         => 'record_code',
                'scope'             => 'scope',
                'project_id'        => '_project_code',
                'wayleave_received' => 'wayleave_received',
                'wayleave received' => 'wayleave_received',
                'wayleave_cleared'  => 'wayleave_cleared',
                'wayleave cleared'  => 'wayleave_cleared',
                'wayleave_pending'  => 'wayleave_pending',
                'wayleave pending'  => 'wayleave_pending',
                'survey_received'   => 'survey_received',
                'survey received'   => 'survey_received',
                'survey_cleared'    => 'survey_cleared',
                'survey cleared'    => 'survey_cleared',
                'survey_pending'    => 'survey_pending',
                'survey pending'    => 'survey_pending',
                'paps'              => 'paps',
                'comp_paid_zmw'     => 'comp_paid_zmw',
                'notes'             => 'notes',
            ],
            'programme_outputs' => [
                'programme_output_id' => 'output_code',
                'output_code'         => 'output_code',
                'programme'           => 'programme',
                'period'              => 'period',
                'connections_delivered'    => 'connections_delivered',
                'connections delivered'    => 'connections_delivered',
                'transformers_energised'  => 'transformers_energised',
                'transformers energised'  => 'transformers_energised',
                'jobs_pending_connection' => 'jobs_pending_connection',
                'jobs pending connection' => 'jobs_pending_connection',
                'notes'                   => 'notes',
            ],
            'grid_studies' => [
                'study_id'             => 'study_code',
                'study_code'           => 'study_code',
                'study id'             => 'study_code',
                'project_id'           => '_project_code',
                'study_type'           => 'study_type',
                'study type'           => 'study_type',
                'name'                 => 'name',
                'capacity_mw'          => 'capacity_mw',
                'developer'            => 'developer',
                'technology'           => 'technology',
                'project_area'         => 'project_area',
                'point_of_connection'  => 'point_of_connection',
                'progress_pct'         => 'progress_pct',
                'stage_received'       => 'stage_received',
                'stage_not_started'    => 'stage_not_started',
                'stage_under_review'   => 'stage_under_review',
                'stage_pending_client' => 'stage_pending_client',
                'stage_revisions'      => 'stage_revisions',
                'stage_approved'       => 'stage_approved',
                'notes'                => 'notes',
            ],
            'workstreams' => [
                'workstream_code'  => 'workstream_code',
                'workstream_id'    => 'workstream_code',
                'project_id'       => '_project_code',
                'project_name'     => '_project_name',
                'project name'     => '_project_name',
                'workstream'       => 'workstream',
                'planned_%'        => 'planned_pct',
                'planned_pct'      => 'planned_pct',
                'planned %'        => 'planned_pct',
                'actual_%'         => 'actual_pct',
                'actual_pct'       => 'actual_pct',
                'actual %'         => 'actual_pct',
                'variance'         => 'variance_pct',
                'variance_%'       => 'variance_pct',
                'variance_pct'     => 'variance_pct',
                'status'           => 'status',
                'comments'         => 'comments',
            ],
        ];
    }

    /**
     * Get the list of model fields available for a given entity.
     */
    public static function availableFields(string $entity): array
    {
        return match ($entity) {
            'projects' => [
                'project_code', 'project_name', 'sector', 'sub_sector', 'status', 'programme',
                'province', 'district', 'contractor', 'developer',
                'cost_usd', 'cost_zmw', 'capacity_mw', 'commissioned_mw', 'progress_pct',
                'cod_planned', 'key_issue_summary', 'last_update_date', 'notes',
                'energy_type', 'renewable_flag', 'market_segment', 'ownership_model',
                'owner_group', 'owner_entity', 'is_ipp', 'commissioned_mw_to_date',
                'grid_connected', 'cod_actual', 'commissioned_date',
                'owner_subsidiary_name', 'owner_subsidiary_flag', 'commissioned_capacity_mw',
                'lifecycle_phase', 'project_manager', 'planned_start', 'planned_finish',
                'forecast_finish', 'approved_budget', 'committed_cost', 'actual_spend',
                'forecast_at_completion', 'next_decision',
            ],
            'milestones' => [
                'milestone_code', 'pp_project_id', 'milestone', 'category',
                'baseline_date', 'forecast_date', 'actual_date', 'weight_pct',
                'delay_days', 'owner', 'status', 'notes',
            ],
            'financials' => [
                'finance_code', 'pp_project_id', 'as_of_date',
                'committed_amount', 'paid_to_date', 'currency', 'notes',
            ],
            'risks' => [
                'risk_code', 'record_type', 'pp_project_id', 'risk_category',
                'risk_description', 'likelihood', 'impact', 'severity', 'risk_level',
                'mitigation', 'owner', 'due_date', 'status',
                'created_date', 'days_open', 'notes',
            ],
            'safeguards' => [
                'record_code', 'scope', 'pp_project_id',
                'wayleave_received', 'wayleave_cleared', 'wayleave_pending',
                'survey_received', 'survey_cleared', 'survey_pending',
                'paps', 'comp_paid_zmw', 'notes',
            ],
            'programme_outputs' => [
                'output_code', 'programme', 'period',
                'connections_delivered', 'transformers_energised',
                'jobs_pending_connection', 'notes',
            ],
            'grid_studies' => [
                'study_code', 'pp_project_id', 'study_type', 'name',
                'capacity_mw', 'developer', 'technology', 'project_area',
                'point_of_connection', 'progress_pct',
                'stage_received', 'stage_not_started', 'stage_under_review',
                'stage_pending_client', 'stage_revisions', 'stage_approved', 'notes',
            ],
            'workstreams' => [
                'workstream_code', 'pp_project_id', 'workstream',
                'planned_pct', 'actual_pct', 'variance_pct', 'status', 'comments',
            ],
            default => [],
        };
    }
}
