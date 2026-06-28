<?php

namespace App\Services\Product;

use App\Models\Product\ProductDetails;
use Illuminate\Support\Facades\Log;

class ProductDetailsService
{
    public function create(array $data): void
    {
        ProductDetails::create([
            ...$data,
        ]);

        Log::info('Product Details added successfully');

    }

    public function update(array $data, int $product_details_id): void
    {
        $data = array_filter($data, fn($value) => !is_null($value));

        ProductDetails::where('id', $product_details_id)
            ->firstOrFail()
            ->update([
                ...$data,
            ]);

        Log::info("Product Details (ID: $product_details_id) updated successfully");

    }

}
