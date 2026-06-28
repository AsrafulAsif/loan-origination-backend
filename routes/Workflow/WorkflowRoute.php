<?php


use App\Http\Controllers\Workflow\WorkflowController;

Route::prefix('workflow')->group(function () {
    Route::middleware(['auth:sanctum',])->group(function () {
        Route::post('/stage/create', [WorkflowController::class, 'createWorkFlowStage']);
        route::post('/sync-stage-to-role', [WorkflowController::class, 'syncStagesToRole']);
        Route::get('/stage/all', [WorkflowController::class, 'getAllWorkFlowStages']);
        Route::post('/definition/create', [WorkflowController::class, 'createWorkFlowDefinition']);
        Route::get('/definition/all', [WorkflowController::class, 'getAllWorkFlowDefinitions']);
        Route::get('/definition/{workflow_definition_id}', [WorkflowController::class, 'getAllWorkFlowDefinitionWithStages']);
    });
});
