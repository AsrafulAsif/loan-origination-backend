<?php

namespace App\Models\Workflow;

use App\Models\BaseModel;

class WorkflowStage extends BaseModel
{
    protected $connection = 'mysql';

    protected $table = 'workflow_stages';

    protected $fillable = [
        'stage_code',
        'stage_name',
        'stage_type',
        'stage_description',
    ];
}
