<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PpGridImpactStudyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->canInputData();
    }

    public function rules(): array
    {
        return [
            'study_code'           => [
                'required', 'string', 'max:30',
                Rule::unique('pp_grid_impact_studies', 'study_code')->ignore($this->route('grid_impact_study')),
            ],
            'pp_project_id'        => 'nullable|exists:pp_projects,id',
            'study_type'           => 'required|string|in:report,inception',
            'name'                 => 'required|string|max:255',
            'capacity_mw'          => 'nullable|numeric|min:0',
            'developer'            => 'nullable|string|max:255',
            'technology'           => 'nullable|string|max:100',
            'project_area'         => 'nullable|string|max:255',
            'point_of_connection'  => 'nullable|string|max:255',
            'progress_pct'         => 'nullable|numeric|min:0|max:100',
            'stage_received'       => 'boolean',
            'stage_not_started'    => 'boolean',
            'stage_under_review'   => 'boolean',
            'stage_pending_client' => 'boolean',
            'stage_revisions'      => 'boolean',
            'stage_approved'       => 'boolean',
            'notes'                => 'nullable|string|max:5000',
        ];
    }
}
