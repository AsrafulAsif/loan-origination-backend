<?php

namespace App\Models\Template;

use App\Models\BaseModel;

class FormTemplateGlobalCalculations extends BaseModel
{
    protected $connection = 'mysql';

    protected $table = 'form_template_global_calculations';

    protected $fillable = [
        'form_template_id',
        'calculation_id',
        'formula',
        'dependencies',
        'result_field_key',
    ];
}