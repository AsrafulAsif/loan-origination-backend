<?php

namespace App\Services\Privilege;

use App\Models\Privilege\Role;
use App\Models\Privilege\UserRoles;
use App\Models\Workflow\WorkflowStage;
use App\Traits\UserSnapshotTrait;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class RoleService
{
    use UserSnapshotTrait;

    public function create(array $data): void
    {
        Role::create([
            'role_name' => $data['role_name'],
            'role_display_name' => $data['role_display_name'],
            'role_description' => $data['role_description'],
        ]);
    }


    public function getAllRoles(): Collection
    {
        $roles = Role::oldest()->get();

        $roleIds = $roles->pluck('id')->toArray();

        $stages = WorkflowStage::join('role_stages', 'workflow_stages.id', '=', 'role_stages.stage_id')
            ->whereIn('role_stages.role_id', $roleIds)
            ->where('role_stages.is_active', true)
            ->orderBy('workflow_stages.id')
            ->select('workflow_stages.*', 'role_stages.role_id')
            ->get()
            ->groupBy('role_id');

        $roles->each(function ($role) use ($stages) {
            $role->stages = $stages->get($role->id, collect());
        });

        return $roles;
    }

//    public function getAllRoles2(): array
//    {
//        return WorkflowStage::oldest()
//            ->get()
//            ->map(function ($stage) {
//                return [
//                    'id' => $stage->id,
//                    'role_name'=>$stage->stage_code,
//                    'role_display_name' =>$stage->stage_name,
//                    'role_description' => $stage->stage_description,
//                    'is_active' => $stage->is_active,
//                ];
//            })->toArray();
//    }

    public function update(int $role_id, array $data): void
    {
        $data = array_filter($data, fn($value) => !is_null($value));

        $role = Role::where('id', $role_id)
            ->firstOrFail();

        $role->update([
            ...$data,
        ]);

        Log::info("Role (ID: $role_id) updated successfully");
    }

    public function delete(int $role_id): void
    {
        $employeeId = auth()->id();
        //TODO delete role is pending.
        Log::info("Soft delete job dispatched for Role ID: $role_id");
    }

    public function getRoleById(int $role_id): Role
    {
        $role = Role::where('id', $role_id)->firstOrFail();

        $role->stages = WorkflowStage::join('role_stages', 'workflow_stages.id', '=', 'role_stages.stage_id')
            ->where('role_stages.role_id', $role_id)
            ->where('role_stages.is_active', true)
            ->orderBy('workflow_stages.id')
            ->select('workflow_stages.*')
            ->get();

        return $role;
    }

    public function syncRolesToUser(array $data): void
    {
        $employeeId = $data['employee_id'];
        $requestedRoles = $data['role_ids'];

        $currentRoleIds = UserRoles::where('employee_id', $employeeId)
            ->where('is_active', true)
            ->pluck('role_id')
            ->toArray();

        $toAssign = array_diff($requestedRoles, $currentRoleIds);
        $toUnassign = array_diff($currentRoleIds, $requestedRoles);
        $skipped = array_intersect($requestedRoles, $currentRoleIds);

        // Unassign removed roles
        if (!empty($toUnassign)) {
            UserRoles::where('employee_id', $employeeId)
                ->whereIn('role_id', $toUnassign)
                ->update(['is_active' => false]);
        }

        // Assign new roles
        if (!empty($toAssign)) {
            array_map(fn($roleId) => [
                'employee_id' => $employeeId,
                'role_id' => $roleId,
                'assign_by' => $this->getUserSnapshot(),
                'assigned_at' => now(),
                'is_active' => true,
            ], $toAssign);

            // Handle previously unassigned roles (re-activation) vs brand new
            foreach ($toAssign as $roleId) {
                UserRoles::updateOrCreate(
                    ['employee_id' => $employeeId, 'role_id' => $roleId],
                    [
                        'assign_by' => $this->getUserSnapshot(),
                        'assigned_at' => now(),
                        'is_active' => true,
                    ]
                );
            }
        }

        Log::info("Roles synced for user {$employeeId}", [
            'assigned' => array_values($toAssign),
            'unassigned' => array_values($toUnassign),
            'skipped' => array_values($skipped),
        ]);
    }

}
