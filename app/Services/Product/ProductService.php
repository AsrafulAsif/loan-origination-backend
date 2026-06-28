<?php

namespace App\Services\Product;

use App\Models\Product\Product;
use App\Models\Product\ProductDetails;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProductService
{
    public function create(array $data): void
    {
        Product::create([
            ...$data,
        ]);

        Log::info('Product added successfully');

    }

    /**
     * @throws Throwable
     */
    public function createProductWithDetails(array $data): void
    {
        DB::transaction(function () use ($data) {
            $product = Product::create([
                ...$data,
            ]);
            foreach ($data['details'] as $detail) {
                ProductDetails::create([
                    'product_id' => $product->id,
                    ...$detail,
                ]);
            }
        });

    }

    public function update(array $data, int $product_id): void
    {
        $data = array_filter($data, fn($value) => !is_null($value));

        Product::where('id', $product_id)
            ->firstOrFail()
            ->update([
                ...$data,
            ]);

        Log::info("Product (ID: $product_id) updated successfully");

    }

    public function getAllProducts(): Collection
    {
        return Product::oldest()
            ->get();
    }

    public function getProductById(int $id): Product
    {
        return Product::where('id', $id)
            ->where('is_active', true)
            ->firstOrFail();
    }

    public function getProductWithProductDetails(int $productId): array
    {
        $product = $this->getProductById($productId);
        $productDetails = ProductDetails::where('product_id', $productId)
            ->where('is_active', true)
            ->get();
        return [
            'product' => $product,
            'productDetails' => $productDetails
        ];
    }

}
