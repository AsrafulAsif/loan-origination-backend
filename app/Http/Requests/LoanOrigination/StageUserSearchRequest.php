<?php

namespace App\Http\Requests\LoanOrigination;

use Illuminate\Foundation\Http\FormRequest;

class StageUserSearchRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
