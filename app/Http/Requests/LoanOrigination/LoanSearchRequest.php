<?php

namespace App\Http\Requests\LoanOrigination;

use Illuminate\Foundation\Http\FormRequest;

class LoanSearchRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'loan_id'           => ['nullable', 'string', 'max:100'],
            'product_name'      => ['nullable', 'string', 'max:255'],
            'product_code'      => ['nullable', 'string', 'max:100'],
            'branch_code'       => ['nullable', 'string', 'max:100'],
            'created_by_email'  => ['nullable', 'email', 'max:255'],
            'assigned_to_email' => ['nullable', 'email', 'max:255'],
            'date_from'         => ['nullable', 'date'],
            'date_to'           => ['nullable', 'date', 'after_or_equal:date_from'],
            'page'              => ['nullable', 'integer', 'min:1'],
            'per_page'          => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
