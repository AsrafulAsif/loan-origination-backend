<?php

namespace App\Http\Requests\WorkFlow;

use Illuminate\Foundation\Http\FormRequest;

class WorkflowDefinitionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'workflow_name' => 'required|string|unique:workflow_definitions,workflow_name',
            'workflow_definition' => 'required|array|min:1',
            'workflow_definition.*' => 'required|integer|exists:workflow_stages,id',
        ];
    }
}
