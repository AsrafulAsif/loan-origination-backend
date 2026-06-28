<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Template\TemplateController;

Route::prefix('template')->group(function () {
    Route::middleware(['auth:sanctum',])->group(function () {
        Route::post('/draft', [TemplateController::class, 'createDraftTemplate']);
        Route::put('/draft', [TemplateController::class, 'updateDraftTemplate']);
        Route::post('/{templateId}', [TemplateController::class, 'publishTemplate']);
        Route::post('/{templateId}/duplicate', [TemplateController::class, 'duplicateTemplate']);
        Route::delete('/{templateId}', [TemplateController::class, 'deleteDraftTemplate']);
        Route::get('/{templateId}', [TemplateController::class, 'getTemplateById']);
        Route::get('/admin/all-templates', [TemplateController::class, 'getAllTemplatesForAdmin']);
        Route::get('/user/template-by-product/{productId}', [TemplateController::class, 'latestTemplateByProduct']);
        Route::get('/admin/templates-for-dashboard', [TemplateController::class, 'getAllTemplatesForDashboard']);
    });
});
