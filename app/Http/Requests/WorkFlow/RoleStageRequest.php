<?php

namespace App\Http\Requests\WorkFlow;

use Illuminate\Foundation\Http\FormRequest;

class RoleStageRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'role_id' => 'required',
            'stage_ids' => 'nullable |array',
            'stage_ids.*' => 'integer',
        ];
    }
}
