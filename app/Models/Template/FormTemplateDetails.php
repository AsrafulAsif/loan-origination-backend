<?php

namespace App\Models\Template;

use App\Models\BaseModel;

class FormTemplateDetails extends BaseModel
{
    protected $connection = 'mysql';

    protected $table = 'form_template_details';

    protected $fillable = [
        'template_key',
        'template_id',
        'form_template_id',
        'settings_json',
        'template_json',

    ];
}
