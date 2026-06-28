<?php

namespace App\Models\Privilege;

use Illuminate\Database\Eloquent\Model;

class PermissionType extends Model
{
    protected $connection = 'mysql';

    protected $table = 'permission_types';

    public $timestamps = false;


    protected $fillable = [
        'permission_type_name',
    ];

    protected function casts(): array
    {
        return [
            'permission_type_name' => 'string',
        ];
    }
}
