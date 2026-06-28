<?php

namespace App\Http\Requests\Privilege;

use Illuminate\Foundation\Http\FormRequest;

class UserRoleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'employee_id' => 'required|string|exists:mysql_second.apiusers,employee_id',
            'role_ids' => 'nullable|array',
            'role_ids.*' => 'integer|exists:mysql.roles,id',
        ];
    }
}
