<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PpProgrammeOutputRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->canInputData();
    }

    public function rules(): array
    {
        return [
            'output_code'             => [
                'required', 'string', 'max:30',
                Rule::unique('pp_programme_outputs', 'output_code')->ignore($this->route('programme_output')),
            ],
            'programme'               => 'required|string|max:255',
            'period'                  => 'required|string|max:50',
            'connections_delivered'    => 'nullable|integer|min:0',
            'transformers_energised'  => 'nullable|integer|min:0',
            'jobs_pending_connection' => 'nullable|integer|min:0',
            'notes'                   => 'nullable|string|max:2000',
        ];
    }
}
