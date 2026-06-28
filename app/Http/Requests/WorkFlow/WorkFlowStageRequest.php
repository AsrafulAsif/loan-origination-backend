<?php

namespace App\Http\Requests\WorkFlow;

use Illuminate\Foundation\Http\FormRequest;

class WorkFlowStageRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'stage_code'=>'required|string|unique:workflow_stages,stage_code',
            'stage_name' => 'required|string',
            "stage_type" => "nullable|string",
            'stage_description'=>'nullable|string',
        ];
    }
}
