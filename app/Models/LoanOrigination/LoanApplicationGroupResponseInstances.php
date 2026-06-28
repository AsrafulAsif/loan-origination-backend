<?php

namespace App\Models\LoanOrigination;

use App\Models\BaseModel;

class LoanApplicationGroupResponseInstances extends BaseModel
{
    protected $connection = 'mysql';

    protected $table = 'loan_application_group_response_instances';

    protected $fillable = [
        'instance_key',
        'group_response_id',
        'instance_index',
        'is_active'
    ];
}
