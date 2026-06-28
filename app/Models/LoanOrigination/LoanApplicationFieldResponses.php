<?php

namespace App\Models\LoanOrigination;

use App\Models\BaseModel;

class LoanApplicationFieldResponses extends BaseModel
{
    protected $connection = 'mysql';

    protected $table = 'loan_application_field_responses';

    protected $fillable = [
        'loan_application_id',
        'field_key',
        'field_id',
        'group_instance_id',
        'value_json',
        'is_valid',
        'errors',
        'is_active',
        'is_reverted'
    ];
}
