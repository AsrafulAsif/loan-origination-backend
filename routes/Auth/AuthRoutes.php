<?php

use App\Http\Controllers\Auth\AuthController;

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/loginUser', [AuthController::class, 'loginUser']);
        Route::get('/me', [AuthController::class, 'getAUser']);
        Route::get('/profile', [AuthController::class, 'getAUserByEmployeeId']);
        Route::get('/getAllUsers', [AuthController::class, 'getAllUsers']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});
