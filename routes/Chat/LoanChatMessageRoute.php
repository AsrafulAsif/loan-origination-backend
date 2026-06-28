<?php


use App\Http\Controllers\Chat\LoanChatMessagesController;
use Illuminate\Support\Facades\Route;

Route::prefix('message')->group(function () {
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/create', [LoanChatMessagesController::class, 'createMessage']);
        Route::get('/get-message/{loan_application_id}', [LoanChatMessagesController::class, 'loadMessages']);
    });
});
