<?php


use App\Http\Controllers\Product\ProductController;
use Illuminate\Support\Facades\Route;

Route::prefix('product')->group(function () {
    Route::middleware(['auth:sanctum',])->group(function () {
        Route::post('/create', [ProductController::class, 'create']);
        Route::put('/update/{product_id}', [ProductController::class, 'update']);
        Route::get('/all', [ProductController::class, 'getAllProducts']);
        Route::get('/details/{product_id}', [ProductController::class, 'getAllProductWithDetails']);
        Route::post('/create/product-with-details', [ProductController::class, 'createProductWithDetails']);
    });
});
