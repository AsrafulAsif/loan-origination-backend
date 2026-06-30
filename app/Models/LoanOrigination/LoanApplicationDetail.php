<?php

namespace App\Models\LoanOrigination;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LoanApplicationDetail extends Model
{
    use SoftDeletes;

    protected $connection = 'mysql';

    protected $table = 'loan_application_details';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'loan_application_id',
        'data_json',
        'version',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'data_json'                => 'array',
            'is_active'                => 'boolean',
            'created_by'               => 'array',
            'updated_by'               => 'array',
        ];
    }
}
