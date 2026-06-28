<?php

namespace App\Models;

use App\Models\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $field_key
 * @property string $field_id
 * @property string $value_json
 * @property string $is_valid
 * @property string $errors
 * @property string $group_key
 * @property string $group_id
 */
class BaseModel extends Model
{
    use SoftDeletes, Auditable;

    protected $fillable = [
        'loan_application_id',
        'data_json',
        'version',
        'is_active',
        'maker_status',
        'created_by',
        'current_workflow_stage_id',
        'updated_by',
        'current_status',
        'assigned_to',
    ];

    public $timestamps = true;

    protected $attributes = [
        'is_active' => true,
    ];

    protected $hidden = [
        'created_by',
        'updated_by',
        'deleted_by',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected function casts(): array
    {
        return [
            'section_permissions_json' => 'array',
            'conditional_logic'        => 'array',
            'validation_json'          => 'array',
            'table_config'             => 'array',
            'calculated_config'        => 'array',
            'api_trigger_json'         => 'array',
            'settings_json'            => 'array',
            'template_json'            => 'array',
            'workflow_definition'      => 'array',
            'published_at'             => 'datetime',
            'data_json'                => 'array',
            'is_active'                => 'boolean',
            'created_by'               => 'array',
            'updated_by'               => 'array',
            'deleted_by'               => 'array',
            'created_at'               => 'datetime',
            'updated_at'               => 'datetime',
            'deleted_at'               => 'datetime',
        ];
    }
}
