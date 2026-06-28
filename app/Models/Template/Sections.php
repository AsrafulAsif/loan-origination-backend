<?php

namespace App\Models\Template;

use App\Models\BaseModel;

class Sections extends BaseModel
{
    protected $connection = 'mysql';

    protected $table = 'sections';

    protected $fillable = [
        'section_id',
        'section_key',
        'template_id',
        'form_template_id',
        'title',
        'description',
        'section_order',
        'section_permissions_json',
        'columns',
        'conditional_logic',
        'enabled',
        'is_collapsible',
        'default_collapsed',
        'show_progress',
        'class_name',
        'header_class_name',
        'content_class_name',
    ];
}
