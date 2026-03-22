<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PpProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->canInputData();
    }

    public function rules(): array
    {
        return [
            'project_code'      => [
                'required', 'string', 'max:20',
                Rule::unique('pp_projects', 'project_code')->ignore($this->route('project')),
            ],
            'project_name'      => 'required|string|max:255',
            'sector'            => 'required|string|in:Generation,Transmission,Distribution,IPP',
            'sub_sector'        => 'nullable|string|max:255',
            'status'            => 'required|string|in:Execution,Preparation,Completed,Cancelled,Commissioned',
            'programme'         => 'nullable|string|max:255',
            'province'          => 'nullable|string|max:255',
            'district'          => 'nullable|string|max:255',
            'contractor'        => 'nullable|string|max:255',
            'developer'         => 'nullable|string|max:255',
            'funder'            => 'nullable|string|max:255',
            'funding_type'      => 'nullable|string|max:30',
            'cost_usd'          => 'nullable|numeric|min:0',
            'cost_zmw'          => 'nullable|numeric|min:0',
            'capacity_mw'       => 'nullable|numeric|min:0',
            'commissioned_mw'   => 'nullable|numeric|min:0',
            'progress_pct'      => 'nullable|numeric|min:0|max:100',
            'cod_planned'       => 'nullable|date',
            'key_issue_summary' => 'nullable|string|max:5000',
            'last_update_date'  => 'nullable|date',
            'rag_status'        => 'required|string|in:Red,Amber,Green',
            'notes'             => 'nullable|string|max:5000',
        ];
    }
}
