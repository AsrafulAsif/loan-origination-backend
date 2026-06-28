<?php

namespace App\Models\Privilege;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Permission2 extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'permission_name',
        'permission_display_name',
        'permission_description',
        'permission_type',
        'is_active',
        'api_url',
        'controller_name',
        'method_name',
        'created_by',
        'updated_by',
    ];
}
