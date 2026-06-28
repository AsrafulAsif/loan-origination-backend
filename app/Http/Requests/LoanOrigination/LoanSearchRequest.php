<?php

namespace App\Http\Requests\LoanOrigination;

use Illuminate\Foundation\Http\FormRequest;

class LoanSearchRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'search'      => ['nullable','string','min:2','max:100'],
            'page'        => ['nullable', 'integer', 'min:1'],
            'per_page'    => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
