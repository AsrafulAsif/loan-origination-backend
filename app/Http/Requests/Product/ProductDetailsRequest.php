<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class ProductDetailsRequest extends FormRequest
{
    public function rules(): array
    {
        $productDetailsId = $this->route('product_details_id') !== null;

        return [
            'product_id' => $productDetailsId ? 'nullable|integer|min:1' : 'required | exists:products,id',
            'role_name' => $productDetailsId ? 'nullable|string|max:255' : 'required|string|max:255',
            'limit_amount' => $productDetailsId ? 'nullable|integer|min:1' : 'required|integer|min:1',
            'customer_type' => $productDetailsId ? 'nullable|string|max:255' : 'required|string|max:255',
        ];
    }
}
