<?php

namespace App\Http\Requests\Privilege;

use Illuminate\Foundation\Http\FormRequest;

class PermissionSearchRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'search' => 'required|string|min:2|max:100',
        ];
    }
}
