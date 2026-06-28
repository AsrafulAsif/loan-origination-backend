<?php

namespace App\Models\Privilege;

use Illuminate\Database\Eloquent\Model;

class UserRoles extends Model
{
    protected $connection = 'mysql';

    protected $table = 'user_roles';

    public $timestamps = false;

    protected $fillable = [
        'employee_id',
        'role_id',
        'assign_by',
        'assigned_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'assign_by' => 'array',
            'assign_at' => 'datetime',
        ];
    }
}
