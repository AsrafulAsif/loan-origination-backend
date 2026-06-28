<?php


use App\Http\Controllers\AccountDetails\AccountDetailsController;
use Illuminate\Support\Facades\Route;

Route::prefix('account-details')->group(function () {
//    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/{bank_account_number}', [AccountDetailsController::class, 'getAccountInfo']);
//    });
});
