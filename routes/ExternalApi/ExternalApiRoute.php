<?php

use App\Http\Controllers\ExternalApi\ExternalApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('external-api')->group(function () {

    // Create External API configuration
    Route::post('/', [ExternalApiController::class, 'create']);

    // Update External API configuration
    Route::put('/{apiCode}', [ExternalApiController::class, 'update']);
    Route::patch('/{apiCode}', [ExternalApiController::class, 'update']);

    // Execute configured external API
    Route::post('/{apiCode}', [ExternalApiController::class, 'callExternalApiData']);
});



