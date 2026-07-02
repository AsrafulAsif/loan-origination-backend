<?php


use App\Http\Controllers\LoanOrigination\LoanOriginationController;
use Illuminate\Support\Facades\Route;

Route::prefix('loan')->group(function () {
    Route::middleware(['auth:sanctum',])->group(function () {
        Route::post('/create/init',[LoanOriginationController::class,'initLoanOrigination']);
        Route::post('/create/draft', [LoanOriginationController::class, 'createLoanDraft']);
        Route::post('/create/submit', [LoanOriginationController::class, 'createLoanSubmit']);
        Route::post('/review', [LoanOriginationController::class, 'loanReview']);
        Route::post('/loans/pick',   [LoanOriginationController::class, 'pickLoan']);
        Route::post('/loans/assign', [LoanOriginationController::class, 'assignLoan']);
        Route::get('/loans/{loan_id}/stage-users', [LoanOriginationController::class, 'getCurrentStageUsers']);
        Route::get('/get-dashboard-loans', [LoanOriginationController::class, 'getDashboardLoans']);
        Route::get("/created-by-me", [LoanOriginationController::class, 'getAllLoansCreatedByMe']);
        Route::get("/ho-reached", [LoanOriginationController::class, 'getHoReachedLoans']);
        Route::get('/get-loan-by-id/{loan_id}', [LoanOriginationController::class, 'getLoanById']);
        Route::get("/tloans", [LoanOriginationController::class, 'getTPlusOneDayLoans']);

    });
});
