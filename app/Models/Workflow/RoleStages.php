<?php

namespace App\Models\Workflow;

use Illuminate\Database\Eloquent\Model;

class RoleStages extends Model
{
    protected $connection = 'mysql';

    protected $table = 'role_stages';

    public $timestamps = false;

    protected $fillable = [
        'role_id',
        'stage_id',
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
            'updated_by' => 'array',
            'updated_at' => 'datetime',
        ];
    }
}
