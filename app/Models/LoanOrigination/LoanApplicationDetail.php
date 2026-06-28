<?php

namespace App\Models\LoanOrigination;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Model;

class LoanApplicationDetail extends BaseModel
{
    protected $connection = 'mysql';

    protected $table = 'loan_application_details';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'loan_application_id',
        'data_json',
        'version',
        'is_active'
    ];
}
