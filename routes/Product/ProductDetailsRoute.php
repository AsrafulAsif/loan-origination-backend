<?php

use App\Http\Controllers\Product\ProductDetailsController;
use Illuminate\Support\Facades\Route;

Route::prefix('product-details')->group(function () {
    Route::middleware(['auth:sanctum',])->group(function () {
        Route::post('/create', [ProductDetailsController::class, 'create']);
        Route::put('/update/{product_details_id}', [ProductDetailsController::class, 'update']);
    });
});
