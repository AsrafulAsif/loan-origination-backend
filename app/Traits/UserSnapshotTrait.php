<?php

namespace App\Traits;

use App\Models\Auth\ApiUser;

trait UserSnapshotTrait
{
    protected function getUserSnapshot(): array
    {
        $user = auth()->user();
        return [
            'employee_id' => $user->employee_id,
            'email_address' => $user->email_address,
            'full_name' => $user->full_name,
            'mobile_no' => $user->mobile_no,
            'branch_code' => $user->orbit_branch_code,
            'desig_name_long'   => $user->desig_name_long,
            "function_name"     => $user->function_name,
        ];
    }

    protected function getUserByEmployeeId(string $employee_id): array
    {
        $user = ApiUser::query()
            ->where('employee_id', $employee_id)
            ->first();

        return [
            'employee_id'       => $user->employee_id,
            'email_address'     => $user->email_address,
            'full_name'         => $user->full_name,
            'mobile_no'         => $user->mobile_no,
            'branch_code'       => $user->orbit_branch_code,
            'desig_name_long'   => $user->desig_name_long,
            "function_name"     => $user->function_name,
        ];
    }

}
