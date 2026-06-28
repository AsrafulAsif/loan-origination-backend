<?php

namespace App\Http\Controllers\Privilege;

use App\Http\Requests\Privilege\RoleRequest;
use App\Http\Requests\Privilege\UserRoleRequest;
use App\Services\Privilege\RoleService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;

class RoleController
{
    use ApiResponseTrait;
    protected RoleService $roleService;
    public function __construct(RoleService $roleService){
        $this->roleService = $roleService;
    }

    public function getAllRoles() : JsonResponse
    {
        $response = $this->roleService->getAllRoles();
        return $this->successResponse($response);
    }

    public function create(RoleRequest $request): JsonResponse
    {
        $this->roleService->create($request->validated());
        return $this->successResponse(null,"Role created",201);
    }
    public function update(RoleRequest $request, int $role_id): JsonResponse
    {
        $this->roleService->update($role_id, $request->validated());
        return $this->successResponse(null,"Role updated");
    }
    public function delete(int $role_id): JsonResponse
    {
        $this->roleService->delete($role_id);
        return $this->successResponse(null,"Role deleted");
    }
    public function getRoleById(int $role_id): JsonResponse
    {
        $response = $this->roleService->getRoleById($role_id);
        return $this->successResponse($response);
    }

    public function syncRolesToUser(UserRoleRequest $request): JsonResponse
    {
        $this->roleService->syncRolesToUser($request->validated());
        return $this->successResponse(null,"Role synced");
    }
}
