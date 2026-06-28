<?php

namespace App\Models\Workflow;

use App\Models\BaseModel;

/**
 * @property string $workflow_definition
 */
class WorkflowDefinition extends BaseModel
{
    protected $connection = 'mysql';

    protected $table = 'workflow_definitions';

    protected $fillable = [
        'workflow_name',
        'workflow_definition', // array
    ];
}
