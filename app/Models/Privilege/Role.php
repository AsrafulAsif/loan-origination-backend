<?php

namespace App\Models\Privilege;

use App\Models\BaseModel;

class Role extends BaseModel
{
    protected $connection = 'mysql';

    protected $table = 'roles';

    protected $fillable = [
        'role_name',
        'role_display_name',
        'role_description',
    ];

}
