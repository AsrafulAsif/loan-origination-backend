<?php

namespace App\Http\Controllers\Privilege;

use App\Http\Requests\Privilege\PermissionRequest;
use App\Http\Requests\Privilege\PermissionSearchRequest;
use App\Http\Requests\Privilege\RolePermissionRequest;
use App\Services\Privilege\PermissionService;
use App\Traits\ApiResponseTrait;

use Illuminate\Http\JsonResponse;

class PermissionController
{
    use ApiResponseTrait;

    protected PermissionService $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    public function getAllPermissions(): JsonResponse
    {
        $response = $this->permissionService->getAllPermission();
        return $this->successResponse($response);
    }

    public function getPermissionTypes(): JsonResponse
    {
        $response = $this->permissionService->getPermissionTypes();
        return $this->successResponse($response);
    }

    public function getAllPermissionByPermissionType(): JsonResponse
    {
        $response = $this->permissionService->getAllPermissionByPermissionType();
        return $this->successResponse($response);
    }

    public function searchPermissions(PermissionSearchRequest $request): JsonResponse
    {
        $permissions = $this->permissionService->search(
            $request->validated()['search']
        );
        return $this->successResponse($permissions);
    }

    public function create(PermissionRequest $request): JsonResponse
    {
        $this->permissionService->create($request->validated());
        return $this->successResponse(null, "Permission created", 201);
    }

    public function update(PermissionRequest $request, int $permission_id): JsonResponse
    {
        $this->permissionService->update($request->validated(), $permission_id);
        return $this->successResponse(null, "Permission updated");
    }

    public function delete(int $permission_id): JsonResponse
    {
        $this->permissionService->delete($permission_id);
        return $this->successResponse(null,"Role deleted");
    }

    public function syncPermissionsToRole(RolePermissionRequest $request): JsonResponse
    {
        $this->permissionService->syncPermissionsToRole($request->validated());
        return $this->successResponse(null,"Permission assigned");
    }

    public function getAllPermissionsByRoleId(int $role_id): JsonResponse
    {
        $response = $this->permissionService->getRolePermissions($role_id);
        return $this->successResponse($response);
    }

    public function getAllPermissionsByUserId(int $employee_id): JsonResponse
    {
        $response = $this->permissionService->getUserPermissions($employee_id);

        return $this->successResponse($response);
    }
}
