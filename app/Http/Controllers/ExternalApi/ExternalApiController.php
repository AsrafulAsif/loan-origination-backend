<?php

namespace App\Http\Controllers\ExternalApi;

use App\Http\Requests\ExternalApi\ExternalApiRequest;
use App\Services\ExternalApiService\ExternalApiService;
use App\Traits\ApiResponseTrait;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use RuntimeException;

class ExternalApiController
{
    use ApiResponseTrait;

    public function __construct(
        protected ExternalApiService $externalApiService
    ) {}

    public function callExternalApiData(string $apiCode, Request $request): JsonResponse
    {
        return $this->successResponse($this->externalApiService->getExternalApiData($apiCode, $request));
    }

    public function create(ExternalApiRequest $request): JsonResponse
    {
        $externalApi = $this->externalApiService->createExternalApi(
            $request->validated()
        );

        return $this->successResponse($externalApi, 201);
    }

    public function update(string $apiCode, ExternalApiRequest $request): JsonResponse
    {
        $externalApi = $this->externalApiService->updateExternalApi(
            $apiCode,
            $request->validated()
        );

        return $this->successResponse($externalApi);
    }

    public function getAll(Request $request): JsonResponse
    {
        return $this->successResponse($this->externalApiService->listExternalApis($request));
    }
}
