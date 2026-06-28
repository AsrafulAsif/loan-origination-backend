<?php

namespace App\Http\Requests\LoanOrigination;

use Illuminate\Foundation\Http\FormRequest;

class LoanPickRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'loan_id' => ['required', 'string'],
        ];
    }
}
