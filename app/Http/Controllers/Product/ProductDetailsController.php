<?php

namespace App\Http\Controllers\Product;

use App\Http\Requests\Product\ProductDetailsRequest;
use App\Models\Product\Product;
use App\Services\Product\ProductDetailsService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;

class ProductDetailsController
{
    use ApiResponseTrait;
    protected ProductDetailsService $productDetailsService;

    public function __construct(ProductDetailsService $productDetailsService)
    {
        $this->productDetailsService = $productDetailsService;
    }

    public function create(ProductDetailsRequest $request): JsonResponse
    {
        $this->productDetailsService->create($request->validated());
        return $this->successResponse(null, "Product Details created", 201);
    }

    public function update(ProductDetailsRequest $request, int $product_details_id): JsonResponse
    {
        $this->productDetailsService->update($request->validated(), $product_details_id);
        return $this->successResponse(null, "Product Details updated", 201);
    }
}
