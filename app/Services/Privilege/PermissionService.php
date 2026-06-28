<?php

namespace App\Services\Privilege;

use App\Models\Auth\ApiUser;
use App\Models\Privilege\Permission;
use App\Models\Privilege\PermissionType;
use App\Models\Privilege\RolePermissions;
use App\Traits\UserSnapshotTrait;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class PermissionService
{
    use UserSnapshotTrait;

    public function create(array $data): void
    {
        $apiUrls = $data['api_urls'] ?? [];

        // No URLs provided — create single permission without URL/method
        if (empty($apiUrls)) {
            Permission::create([
                'permission_name'         => $data['permission_name'],
                'permission_display_name' => $data['permission_display_name'],
                'permission_description'  => $data['permission_description'] ?? null,
                'controller_name'         => $data['controller_name'] ?? null,
                'is_active'               => $data['is_active'] ?? true,
                'api_url'                 => null,
                'method_name'             => null,
                'permission_type_id'      => $data['permission_type_id'],
                'created_by'              => $this->getUserSnapshot(),
                'created_at'              => now(),
            ]);

            Log::info('Permission created without URL/method: ' . $data['permission_name']);
            return;
        }

        // URLs provided — create one permission per URL+method pair, skip duplicates
        foreach ($apiUrls as $apiUrl) {
            $exists = Permission::where('permission_name', $data['permission_name'])
                ->where('api_url', $apiUrl['api_url'])
                ->where('method_name', $apiUrl['method_name'])
                ->exists();

            if ($exists) {
                Log::warning("Permission already exists, skipping: {$data['permission_name']} [{$apiUrl['method_name']}] {$apiUrl['api_url']}");
                continue;
            }

            Permission::create([
                'permission_name'         => $data['permission_name'],
                'permission_display_name' => $data['permission_display_name'],
                'permission_description'  => $data['permission_description'] ?? null,
                'controller_name'         => $data['controller_name'] ?? null,
                'is_active'               => $data['is_active'] ?? true,
                'api_url'                 => $apiUrl['api_url'],
                'method_name'             => $apiUrl['method_name'],
                'permission_type_id'      => $data['permission_type_id'],
                'created_by'              => $this->getUserSnapshot(),
                'created_at'              => now(),
            ]);

            Log::info("Permission created: {$data['permission_name']} [{$apiUrl['method_name']}] {$apiUrl['api_url']}");
        }
    }

    public function getAllPermission(): Collection
    {
        return Permission::orderBy('controller_name')
            ->orderBy('id')
            ->get()
            ->groupBy('controller_name');
    }



    public function getPermissionTypes(): Collection
    {
     return PermissionType::get();
    }

    public function getAllPermissionByPermissionType(): Collection
    {
        return Permission::select(
            'permissions.*',
            'permission_types.permission_type_name as permission_type'
        )
            ->join('permission_types', 'permission_types.id', '=', 'permissions.permission_type_id')
            ->orderBy('permission_types.permission_type_name')
            ->orderBy('controller_name')
            ->orderBy('id')
            ->get()
            ->groupBy(['permission_type', 'controller_name']);
    }

    public function search(string $search): Collection
    {
        return Permission::query()
            ->where(function ($query) use ($search) {
                $query->where('permission_name', 'LIKE', "%{$search}%")
                    ->orWhere('permission_display_name', 'LIKE', "%{$search}%")
                    ->orWhere('permission_description', 'LIKE', "%{$search}%")
                    ->orWhere('controller_name', 'LIKE', "%{$search}%")
                    ->orWhere('api_url', 'LIKE', "%{$search}%")
                    ->orWhere('method_name', 'LIKE', "%{$search}%");
            })
            ->latest()
            ->get();
    }


    public function update(array $data, int $permission_id): void
    {
        $data = array_filter($data, fn($value) => !is_null($value));

        Permission::where('id', $permission_id)
            ->firstOrFail()
            ->update([
                ...$data,
            ]);

        Log::info("Permission (ID: $permission_id) updated successfully");
    }

    public function delete(int $permission_id): void
    {
        //TODO delete permission is pending
        $userId = auth()->id();
        Log::info("Delete Permission successfully. ID: $permission_id");
    }

    public function syncPermissionsToRole(array $data): void
    {
        $roleId               = $data['role_id'];
        $requestedPermissions = $data['permission_ids'];

        $currentPermissionIds = RolePermissions::where('role_id', $roleId)
            ->where('is_active', true)
            ->pluck('permission_id')
            ->toArray();

        $toAssign   = array_diff($requestedPermissions, $currentPermissionIds);
        $toUnassign = array_diff($currentPermissionIds, $requestedPermissions);
        $skipped    = array_intersect($requestedPermissions, $currentPermissionIds);

        if (!empty($toUnassign)) {
            RolePermissions::where('role_id', $roleId)
                ->whereIn('permission_id', $toUnassign)
                ->update(['is_active' => false, 'updated_by' => $this->getUserSnapshot(), 'updated_at' => now()]);
        }

        foreach ($toAssign as $permissionId) {
            RolePermissions::updateOrCreate(
                [
                    'role_id'       => $roleId,
                    'permission_id' => $permissionId,
                ],
                [
                    'is_active'   => true,
                    'created_by' => $this->getUserSnapshot(),
                    'created_at' => now(),
                ]
            );
        }

        Log::info("Permissions synced for role {$roleId}", [
            'assigned'   => array_values($toAssign),
            'unassigned' => array_values($toUnassign),
            'skipped'    => array_values($skipped),
        ]);
    }

    public function getRolePermissions(int $role_id): Collection
    {
        return Permission::join('role_permissions', 'permissions.id', '=', 'role_permissions.permission_id')
            ->where('role_permissions.role_id', $role_id)
            ->where('role_permissions.is_active', true)
            ->orderBy('permissions.id')
            ->select('permissions.*')
            ->get();
    }

    public function getUserPermissions(int $employee_id): array
    {
        $user = ApiUser::findOrFail($employee_id);

        $permissions = Permission::join('role_permissions', 'permissions.id', '=', 'role_permissions.permission_id')
            ->join('roles', 'roles.id', '=', 'role_permissions.role_id')
            ->join('user_roles', 'user_roles.role_id', '=', 'roles.id')
            ->where('user_roles.employee_id', $employee_id)
            ->orderBy('permissions.id')
            ->select('permissions.*')
            ->distinct()
            ->get();
        return [
            'user' => $user,
            'permissions' => $permissions,
        ];
    }

    public function userHasApiPermission(int $employee_id, string $method, string $path): bool
    {
        return Permission::query()
            ->join('role_permissions as rp', function ($join) {
                $join->on('permissions.id', '=', 'rp.permission_id')
                    ->where('rp.is_active', true);
            })
            ->join('roles as r', function ($join) {
                $join->on('r.id', '=', 'rp.role_id')
                    ->where('r.is_active', true);
            })
            ->join('user_roles as ur', function ($join) {
                $join->on('ur.role_id', '=', 'r.id')
                    ->where('ur.is_active', true);
            })
            ->where('ur.employee_id', $employee_id)
            ->where('permissions.is_active', true)
            ->where('permissions.method_name', $method)
            ->where('permissions.api_url', $path)
            ->exists();
    }

}
