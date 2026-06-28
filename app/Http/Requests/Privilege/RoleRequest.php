<?php

namespace App\Http\Requests\Privilege;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoleRequest extends FormRequest
{
    public function rules(): array
    {
        $roleId = $this->route('role_id');

        return [
            'role_name' => [
                $roleId ? 'nullable' : 'required',
                'string',
                'max:255',
            ],

            'role_display_name' => [
                $roleId ? 'nullable' : 'required',
                'string',
                'max:255',
            ],

            'role_description' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],
        ];
    }
}
