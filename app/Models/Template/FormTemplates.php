<?php

namespace App\Models\Template;

use App\Models\BaseModel;

class FormTemplates extends BaseModel
{
    protected $connection = 'mysql';

    protected $table = 'form_templates';

    protected $fillable = [
        'template_key',
        'product_id',
        'title',
        'description',
        'version',
        'status',
    ];
}
