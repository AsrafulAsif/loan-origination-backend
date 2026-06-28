<?php

namespace App\Models\Chat;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LoanChatMessages extends Model
{
    use SoftDeletes;

    protected $connection = 'mysql';

    protected $table = 'loan_chat_messages';

    protected $primaryKey = 'id';

    public $timestamps = true;

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

    protected $fillable = [
        'loan_application_id',
        'message',
        'message_type',
        'from_employee_id',
        'from_employee_name',
        'from_stage',
        'from_stage_name',
        'reply_to',
        'is_active',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at',
        'deleted_at',
    ];
}
