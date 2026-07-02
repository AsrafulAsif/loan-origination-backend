<?php

namespace App\Http\Controllers\ExternalApi;

use App\Services\ExternalApiService\ExternalApiService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExternalApiController
{
    use ApiResponseTrait;
    public function __construct(
        protected ExternalApiService $externalApiService
    ) {}

    public function callExternalApiData(string $apiCode, Request $request) : JsonResponse
    {
        return $this->successResponse($this->externalApiService->getExternalApiData($apiCode, $request));
    }

}
