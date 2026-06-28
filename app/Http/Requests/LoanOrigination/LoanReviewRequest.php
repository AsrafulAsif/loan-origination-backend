<?php

namespace App\Http\Requests\LoanOrigination;

use Illuminate\Foundation\Http\FormRequest;

class LoanReviewRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'loan_id'  => 'required|string',
            'action'   => 'required|string',
            'remarks'  => 'nullable|string',

            // this block only required when action is REVERT
            'send_back_configuration'                                                  => 'required_if:action,REVERT|array',
            'send_back_configuration.current_stage_id'                                 => 'required_if:action,REVERT|integer',
            'send_back_configuration.target_stage_id'                                  => 'required_if:action,REVERT|integer',
            'send_back_configuration.sections'                                         => 'required_if:action,REVERT|array',
            'send_back_configuration.sections.*.sectionKey'                            => 'required|string',
            'send_back_configuration.sections.*.fields'                                => 'nullable|array',
            'send_back_configuration.sections.*.fields.*'                              => 'string',
            'send_back_configuration.sections.*.fieldGroups'                           => 'nullable|array',
            'send_back_configuration.sections.*.fieldGroups.*.fieldGroupKey'           => 'required|string',
            'send_back_configuration.sections.*.fieldGroups.*.instances'               => 'required|array',
            'send_back_configuration.sections.*.fieldGroups.*.instances.*.instanceKey' => 'required|string',
            'send_back_configuration.sections.*.fieldGroups.*.instances.*.fields'      => 'required|array',
            'send_back_configuration.sections.*.fieldGroups.*.instances.*.fields.*'    => 'string',
        ];
    }
}
