<?php

namespace App\Models\Auth;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class ApiUser extends Authenticatable
{
    use HasApiTokens;

    protected $connection = 'mysql_second';

    protected $table = 'apiusers';
    protected $primaryKey = 'employee_id';
    public $incrementing = false;
    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'email_address',
        'full_name',
        'mobile_no',
        'orbit_branch_code',
        'orbit_branch_name',
        'desig_name',
        'desig_name_long',
        'function_name',
        'division_name',
        'emp_status',
        'line_manager_code',
        'emp_gender',
        'dob',
        'joining_date',
        'posting_date',
        'reporting_branch_code',
        'reporting_branch_code2',
        'reporting_branch_name',
        'misys_id',
        'blood_group',
        'account_no',
        'customer_id',
        'timestamp',
    ];
    protected $casts = [
        'dob' => 'date',
        'joining_date' => 'date',
        'posting_date' => 'date',
        'timestamp' => 'datetime',
    ];
}
