<?php

namespace App\Models\ExternalApi;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExternalApi extends Model
{
    use SoftDeletes;

    protected $connection = 'mysql';

    protected $table = 'external_apis';

    protected $fillable = [
        'api_name',
        'api_code',
        'api_base_url',
        'api_method',
        'param_template',
        'request_template',
        'response_template',
        'headers_template',
        'created_by',
        'updated_by',
        'is_active',
    ];


    protected function casts(): array
    {
        return [
            'created_by'      => 'array',
            'updated_by'      => 'array',
            'is_active'       => 'boolean',
        ];
    }
}
