<?php

namespace App\Models\Privilege;

use Illuminate\Database\Eloquent\Model;

class RolePermissions extends Model
{

    protected $connection = 'mysql';

    protected $table = 'role_permissions';

    public $timestamps = false;

    protected $fillable = [
        'role_id',
        'permission_id',
        'is_active',
        'created_by',
        'created_at',
        'updated_by',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'created_by' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
