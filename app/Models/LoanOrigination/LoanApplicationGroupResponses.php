<?php

namespace App\Models\LoanOrigination;

use App\Models\BaseModel;

class LoanApplicationGroupResponses extends BaseModel
{
    protected $connection = 'mysql';

    protected $table = 'loan_application_group_responses';

    protected $fillable = [
        'loan_application_id',
        'group_key',
        'group_id',
        'is_active'
    ];
}
