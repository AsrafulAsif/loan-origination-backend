<?php

namespace App\Models\Template;

use App\Models\BaseModel;

class FieldGroups extends BaseModel
{
    protected $connection = 'mysql';

    protected $table = 'field_groups';

    protected $fillable = [
        'section_id',
        'section_key',
        'group_key',
        'group_id',
        'group_order',
        'title',
        'description',
        'layout',
        'columns',
        'gap',
        'repeatable',
        'min_instances',
        'max_instances',
        'add_button_label',
        'remove_button_label',
        'instance_label',
        'conditional_logic',
        'enabled',
        'class_name',
        'collapsible',
        'default_collapsed',
    ];
}
