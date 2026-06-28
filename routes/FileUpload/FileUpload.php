<?php

use App\Http\Controllers\FileManager\FileUploadController;

Route::prefix('file')->group(function () {
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/upload', [FileUploadController::class, 'uploadMultipleFile']);
        Route::delete('/delete/{filename}', [FileUploadController::class, 'deleteFile']);
        Route::get('/{filename}', [FileUploadController::class, 'serveFile']);
    });

});
