<?php

namespace App\Http\Controllers\Product;

use App\Http\Requests\Product\ProductDetailsRequest;
use App\Http\Requests\Product\ProductRequest;
use App\Http\Requests\Product\ProductWithDetailsRequest;
use App\Services\Product\ProductService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Throwable;

class ProductController
{
    use ApiResponseTrait;

    protected ProductService $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function create(ProductRequest $request): JsonResponse
    {
        $this->productService->create($request->validated());
        return $this->successResponse(null, "Product created", 201);
    }



    public function update(ProductRequest $request, int $product_id): JsonResponse
    {
        $this->productService->update($request->validated(), $product_id);
        return $this->successResponse(null, "Product updated");
    }

    public function getAllProducts(): JsonResponse
    {
        $response = $this->productService->getAllProducts();
        return $this->successResponse($response, "All products");
    }


    /**
     * @throws Throwable
     */
    public function createProductWithDetails(ProductWithDetailsRequest $request): JsonResponse
    {
        $this->productService->createProductWithDetails($request->validated());
        return $this->successResponse(null, "Product and Product Details created", 201);

    }

    public function getAllProductWithDetails(int $product_id): JsonResponse
    {
        $response = $this->productService->getProductWithProductDetails($product_id);
        return $this->successResponse($response, "All products with details");
    }
}
