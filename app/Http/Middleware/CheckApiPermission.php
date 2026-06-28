<?php

namespace App\Http\Middleware;

use App\Services\Privilege\PermissionService;
use App\Traits\ApiResponseTrait;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class CheckApiPermission
{
    use ApiResponseTrait;

    protected PermissionService $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        if (!$this->permissionService->userHasApiPermission(
            employee_id: auth()->user()->id,
            method: $request->method(),
            path: $request->route()->uri()
        )
        ) {
            throw new AccessDeniedHttpException("You don't have permission to edit data flow");
        }
        return $next($request);
    }
}
