<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PpMilestoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->canInputData();
    }

    public function rules(): array
    {
        $uniqueRule = 'unique:pp_milestones,milestone_code';
        if ($this->route('pp_milestone')) {
            $uniqueRule .= ',' . $this->route('pp_milestone');
        }

        return [
            'milestone_code' => "required|string|max:30|{$uniqueRule}",
            'pp_project_id'  => 'required|exists:pp_projects,id',
            'milestone'      => 'required|string|max:255',
            'actual_date'    => 'nullable|date',
            'status'         => 'required|string|in:Completed,In Progress,Pending',
            'notes'          => 'nullable|string|max:2000',
        ];
    }
}
