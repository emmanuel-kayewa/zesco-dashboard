<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PpFinancialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->canInputData();
    }

    public function rules(): array
    {
        return [
            'contract_id'      => 'nullable|string|max:50',
            'pp_project_id'    => 'required|exists:pp_projects,id',
            'as_of_date'       => 'required|date',
            'committed_amount' => 'nullable|numeric|min:0',
            'paid_to_date'     => 'nullable|numeric|min:0',
            'currency'         => 'required|string|in:USD,ZMW',
            'notes'            => 'nullable|string|max:2000',
        ];
    }
}
