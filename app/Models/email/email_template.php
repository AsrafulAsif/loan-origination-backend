<?php

namespace App\Models\email;

use Illuminate\Database\Eloquent\Model;

class email_template extends Model
{
    protected $connection = 'mysql';

    protected $table = 'email_templates';

    public $timestamps = false;
    protected $fillable = [
        'id',
        'subject',
        'body',
        'to_email',
        'cc_email',
        'bcc_email',
        'is_maker_get_email',
        'is_manager_get_email',
        'is_active',
        'created_by',
        'created_at',
        'updated_by',
        'updated_at',
    ];

}
