<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PpWorkstreamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->canInputData();
    }

    public function rules(): array
    {
        return [
            'workstream_code' => [
                'required', 'string', 'max:30',
                Rule::unique('pp_workstreams', 'workstream_code')->ignore($this->route('workstream')),
            ],
            'pp_project_id'   => 'required|exists:pp_projects,id',
            'workstream'      => 'required|string|in:Engineering,Procurement,Construction,Commissioning',
            'planned_pct'     => 'nullable|numeric|min:0|max:100',
            'actual_pct'      => 'nullable|numeric|min:0|max:100',
            'variance_pct'    => 'nullable|numeric',
            'status'          => 'nullable|string|in:On Track,At Risk,Delayed',
            'comments'        => 'nullable|string|max:2000',
        ];
    }
}
