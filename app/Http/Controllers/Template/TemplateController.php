<?php

namespace App\Http\Controllers\Template;

use App\Http\Requests\Template\TemplateCreateRequest;
use App\Services\Template\TemplateService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Throwable;
use Illuminate\Http\Request;

class TemplateController
{
    use ApiResponseTrait;

    protected TemplateService $templateService;
    public function __construct(TemplateService $templateService)
    {
        $this->templateService = $templateService;
    }

    /**
     * @throws Throwable
     */
    public function createTemplate(
        TemplateCreateRequest $request
    ): JsonResponse {

        $this->templateService->create($request->validated());

        return $this->successResponse(null, 'Template Creation successful', 201);
    }

    /**
     * @throws Throwable
     */
    public function publishTemplate(
        int $templateId
    ): JsonResponse
    {
        $this->templateService->publishTemplate($templateId);

        return $this->successResponse(null, 'Template Published successful', 201);
    }
    
    /**
     * @throws Throwable
     */
    public function duplicateTemplate(
        int $templateId
    ): JsonResponse
    {
        $newTemplateId = $this->templateService->duplicateTemplate($templateId);

        return $this->successResponse(['id' => $newTemplateId], 'Template Duplicated successful', 201);
    }


    /**
     * @throws Throwable
     */
    public function createDraftTemplate(
        Request $request
    ): JsonResponse {

        $id = $this->templateService->createDraft($request->all());

        return $this->successResponse(['id' => $id], 'Template Draft Saved successful', 201);
    }


    /**
     * @throws Throwable
     */
    public function updateDraftTemplate(
        Request $request
    ): JsonResponse {

        $this->templateService->updateDraft($request->all());

        return $this->successResponse(null, 'Template Draft Saved successful', 201);
    }

    /**
     * @throws Throwable
     */
    public function deleteDraftTemplate(
        int $templateId
    ): JsonResponse {
        $result = $this->templateService->deleteDraftTemplate($templateId);
        if($result) {
            return $this->successResponse(null, 'Template Deleted successful', 200);
        }
        return $this->errorResponse('Published templates cannot be deleted', 403);
    }


    /**
     * @throws Throwable
     */
    public function getAllTemplatesForAdmin(): JsonResponse
    {
        $data = $this->templateService->getAllTemplates();

        return $this->successResponse($data, 'Templates retrieved successfully', 200);
    }


    /**
     * @throws Throwable
     */
    public function getAllTemplatesForDashboard(): JsonResponse
    {
        $data = $this->templateService->getAllTemplatesForDashboard();

        return $this->successResponse($data, 'Templates retrieved successfully', 200);
    }


    /**
     * @throws Throwable
     */
    public function latestTemplateByProduct(int $productId): JsonResponse
    {
        $data = $this->templateService->getUpdatedTemplateByProductId($productId, null);

        return $this->successResponse($data, 'Latest template retrieved successfully', 200);
    }


    /**
     * @throws Throwable
     */
    public function getTemplateById (int $templateId): JsonResponse
    {
        $data = $this->templateService->getTemplateById($templateId);

        return $this->successResponse($data, 'Template retrieved successfully', 200);
    }
}
