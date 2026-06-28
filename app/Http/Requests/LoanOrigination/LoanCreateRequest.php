<?php

namespace App\Http\Requests\LoanOrigination;

use Illuminate\Foundation\Http\FormRequest;

class LoanCreateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'loan_id' => 'nullable|string',
            'product_id' => 'required|integer',
            'form_template_id' => 'required|integer',
            'remarks' => 'nullable|string',

            'data_json' => 'required|array',
            'data_json.template_id' => 'required|integer',
            'data_json.product_id' => 'required|integer',
            'data_json.sections' => 'required|array|min:1',
            'data_json.sections.*.sectionKey' => 'required|string',
            'data_json.sections.*.fields' => 'nullable|array',
            'data_json.sections.*.fieldGroups' => 'nullable|array',

            'data_json.sections.*.fieldGroups.*.groupKey' => 'required|string',
            'data_json.sections.*.fieldGroups.*.instances' => 'required|array',
            'data_json.sections.*.fieldGroups.*.instances.*.instanceId' => 'required|string',
            'data_json.sections.*.fieldGroups.*.instances.*.fields' => 'nullable|array',
        ];
    }
}
