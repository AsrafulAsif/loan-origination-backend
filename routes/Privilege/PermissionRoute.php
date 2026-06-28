<?php

use App\Http\Controllers\Privilege\PermissionController;
use Illuminate\Support\Facades\Route;

Route::prefix('permission')->group(function () {
    Route::middleware(['auth:sanctum',])->group(function () {
        Route::post('/create', [PermissionController::class, 'create']);
        Route::get('/all', [PermissionController::class, 'getAllPermissions']);
        Route::get('/get-permission-types', [PermissionController::class, 'getPermissionTypes']);
        Route::get('/all-by-permission-types', [PermissionController::class, 'getAllPermissionByPermissionType']);
        Route::get('/search', [PermissionController::class, 'searchPermissions']);
        route::put('/update/{permission_id}', [PermissionController::class, 'update']);
        route::delete('/delete/{permission_id}', [PermissionController::class, 'delete']);
        route::post('/sync-permission-to-role', [PermissionController::class, 'syncPermissionsToRole']);
        route::get('/get-all-by-role-id/{role_id}', [PermissionController::class, 'getAllPermissionsByRoleId']);
        route::get('/get-all-by-user-id/{employee_id}', [PermissionController::class, 'getAllPermissionsByUserId']);
    });
});

