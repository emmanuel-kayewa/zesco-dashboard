<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PpWeeklyReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // TODO: re-enable PP access restriction after development
    }

    public function rules(): array
    {
        $reportId = $this->route('weekly_report');

        return [
            'week_number'  => [
                'required', 'integer', 'min:1', 'max:53',
                Rule::unique('pp_weekly_reports')->where('year', $this->input('year'))->ignore($reportId),
            ],
            'year'         => 'required|integer|min:2020|max:2099',
            'report_date'  => 'required|date',
            'notes'        => 'nullable|string|max:10000',

            // Sections
            'sections'                          => 'required|array|min:1|max:10',
            'sections.*.title'                  => 'required|string|max:255',
            'sections.*.section_type'           => 'required|string|in:completed_solar,construction_projects,transmission_projects,net_metering',
            'sections.*.sort_order'             => 'required|integer|min:0',

            // Project entries (sections 1-3)
            'sections.*.project_entries'                  => 'nullable|array',
            'sections.*.project_entries.*.name'           => 'required|string|max:255',
            'sections.*.project_entries.*.location'       => 'nullable|string|max:255',
            'sections.*.project_entries.*.developer'      => 'nullable|string|max:255',
            'sections.*.project_entries.*.size_mw'        => 'nullable|numeric|min:0',
            'sections.*.project_entries.*.project_type'   => 'nullable|string|max:255',
            'sections.*.project_entries.*.est_completion'  => 'nullable|string|max:500',
            'sections.*.project_entries.*.notes'          => 'nullable|string|max:2000',
            'sections.*.project_entries.*.sort_order'     => 'required|integer|min:0',

            // Net metering entries (section 4)
            'sections.*.net_metering_entries'                     => 'nullable|array',
            'sections.*.net_metering_entries.*.key_initiative'    => 'required|string|max:255',
            'sections.*.net_metering_entries.*.status_value'      => 'required|string|max:255',
            'sections.*.net_metering_entries.*.sort_order'        => 'required|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'week_number.unique' => 'A report for this week already exists.',
        ];
    }
}
