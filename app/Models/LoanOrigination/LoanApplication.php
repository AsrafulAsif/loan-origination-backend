<?php

namespace App\Models\LoanOrigination;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


/**
 * @property string $current_workflow_stage_id
 */
class LoanApplication extends Model
{
    use SoftDeletes;

    protected $connection = 'mysql';

    protected $table = 'loan_applications';
    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'loan_id',
        'product_id',
        'form_template_id',
        'workflow_definition_id',
        'current_workflow_stage_id',
        'current_status',
        'maker_status',
        'assigned_to',
        'branch_code',
        'is_active',
        'reached_ho',
        'created_by',
        'updated_by',
        'reverted'
    ];

    protected function casts(): array
    {
        return [
            'is_active'                => 'boolean',
            'reached_ho'            => 'boolean',
            'assigned_to'              => 'array',
            'created_by'               => 'array',
            'updated_by'               => 'array',
            'reverted'                 => 'boolean',
        ];
    }
}
