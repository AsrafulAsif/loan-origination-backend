<?php

namespace App\Models\File;

use Illuminate\Database\Eloquent\Model;

class FileManager extends Model
{
    protected $connection = 'mysql';

    protected $table = 'file_manager';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'loan_id',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
        'is_lock',
        'uploaded_by',
        'uploaded_at',
    ];

    protected function casts(): array
    {
        return [
            'loan_id' => 'string',
            'file_size' => 'string',
            'uploaded_by' => 'array',
            'uploaded_at' => 'datetime',
        ];
    }
}
