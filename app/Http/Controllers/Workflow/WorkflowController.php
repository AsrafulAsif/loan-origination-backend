<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Requests\WorkFlow\RoleStageRequest;
use App\Http\Requests\WorkFlow\WorkflowDefinitionRequest;
use App\Http\Requests\WorkFlow\WorkFlowStageRequest;
use App\Services\Workflow\WorkflowService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;

class WorkflowController
{
    use ApiResponseTrait;

    protected WorkflowService $workflowService;
    public function __construct(WorkflowService $workflowService){
        $this->workflowService = $workflowService;
    }

    public function createWorkFlowStage(WorkFlowStageRequest $request): JsonResponse
    {
        $response  = $this->workflowService->createWorkFlowStage($request->validated());
        return $this->successResponse($response, "WorkFlow Stage created", 201);
    }

    public function getAllWorkFlowStages(): JsonResponse
    {
     $response = $this->workflowService->getAllWorkFlowStages();
     return $this->successResponse($response, "Workflow Stage list", 201);
    }

    public function createWorkFlowDefinition(WorkflowDefinitionRequest $request): JsonResponse
    {
        $response = $this->workflowService->createWorkFlowDefinition($request->validated());
        return $this->successResponse($response, "WorkFlow Definition created", 201);
    }

    public function getAllWorkFlowDefinitions(): JsonResponse
    {
        $response = $this->workflowService->getAllWorkFlowDefinitions();
        return $this->successResponse($response, "All Workflow definitions");
    }

    public function getAllWorkFlowDefinitionWithStages(int $workflow_definition_id): JsonResponse
    {
        $response = $this->workflowService->getAllWorkFlowDefinitionWithStages($workflow_definition_id);
        return $this->successResponse($response, "All Workflow definition with stages");
    }

    public function syncStagesToRole(RoleStageRequest $request): JsonResponse
    {
        $this->workflowService->syncStagesToRole($request->validated());
        return $this->successResponse(null,"Permission assigned");
    }

}
