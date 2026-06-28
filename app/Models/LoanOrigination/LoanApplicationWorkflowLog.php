<?php

namespace App\Models\LoanOrigination;

use Illuminate\Database\Eloquent\Model;

class LoanApplicationWorkflowLog extends Model
{
    protected $table      = 'loan_application_workflow_logs';
    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'loan_application_id',
        'from_stage_id',
        'to_stage_id',
        'stage_status',
        'action_type',
        'action_by',
        'action_at',
        'remarks',
        'send_back_config',
        'revert_pending'
    ];

    protected $casts = [
        'remarks'     => 'string',
        'action_by'   => 'array',
        'action_at'   => 'datetime',
        'action_type' => 'string',
        'send_back_config' => 'array',
        'revert_pending' => 'boolean'
    ];
}
