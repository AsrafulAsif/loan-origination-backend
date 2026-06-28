<?php

namespace App\Http\Requests\LoanOrigination;

use Illuminate\Foundation\Http\FormRequest;

class LoanAssignRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'loan_id'     => ['required', 'string'],
            'employee_id' => ['required', 'string'],
            'remarks'     => ['nullable', 'string', 'max:500'],
        ];
    }
}
