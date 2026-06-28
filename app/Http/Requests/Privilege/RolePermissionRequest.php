<?php

namespace App\Http\Requests\Privilege;

use Illuminate\Foundation\Http\FormRequest;

class RolePermissionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'role_id' => 'required',
            'permission_ids' => 'nullable |array',
            'permission_ids.*' => 'integer|exists:mysql.permissions,id',
        ];
    }
}
