<?php

namespace App\Models\Template;

use App\Models\BaseModel;

class Fields extends BaseModel

{
     protected $connection = 'mysql';

    protected $table = 'fields';

   protected $fillable = [
       'section_id',
       'field_id',
       'label',
       'field_type',
       'required',
       'placeholder',
       'help_text',
       'default_value',
       'col_span',
       'row_span',
       'field_order',
       'field_group_id',
       'options_source',
       'api_endpoint',
       'options_field_reference',
       'validation_json',
       'allowed_file_types',
       'max_file_size',
       'multiple_files',
       'table_config',
       'calculated_config',
       'conditional_logic',
       'enabled',
       'read_only',
       'depends_on',
       'repeater_fields',
       'repeater_config',
       'api_trigger_json',
       'action_button_json',
       'class_name',
       'label_class_name',
       'input_class_name',
       'field_key',
       'section_key',
       'group_id',
       'group_key',
   ];
}
