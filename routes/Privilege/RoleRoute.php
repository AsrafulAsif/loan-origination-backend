<?php

use App\Http\Controllers\Privilege\RoleController;
use Illuminate\Support\Facades\Route;

Route::prefix('role')->group(function () {
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/create', [RoleController::class, 'create']);
        Route::get('/details/{role_id}', [RoleController::class, 'getRoleById']);
        Route::get('/all', [RoleController::class, 'getAllRoles']);
        Route::put('/update/{role_id}', [RoleController::class, 'update']);
        Route::delete('/delete/{role_id}', [RoleController::class, 'delete']);
        Route::put('/sync-role-to-user', [RoleController::class, 'syncRolesToUser']);
    });
});
