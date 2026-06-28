<?php

namespace App\Models\Template;

use App\Models\BaseModel;

class FieldOptions extends BaseModel
{
    protected $connection = 'mysql';

    protected $table = 'field_options';

    protected $fillable = [
        'field_id',
        'option_label',
        'option_value',
        'disabled',
        'option_order',
        'field_key',
    ];
}
