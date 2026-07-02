<?php
use App\Http\Controllers\ExternalApi\ExternalApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('external-api')->group(function () {
    Route::post('/{apiCode}', [ExternalApiController::class, 'callExternalApiData']);
});
