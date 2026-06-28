<?php

namespace App\Models\Privilege;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $connection = 'mysql';

    protected $table = 'permissions';

    public $timestamps = false;


    protected $fillable = [
        'permission_name',
        'permission_display_name',
        'permission_description',
        'controller_name',
        'api_url',
        'method_name',
        'is_active',
        'permission_type_id',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'created_by' => 'array',
            'updated_by' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }
}
