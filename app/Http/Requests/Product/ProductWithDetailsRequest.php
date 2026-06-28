<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductWithDetailsRequest extends FormRequest
{
    public function rules(): array
    {

        $productId = $this->route('product_id');

        return [

            'product_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('products', 'product_name')->ignore($productId),
            ],
            'product_code' => $productId
                ? 'nullable|string|max:255'
                : 'required|string|max:255',
            'workflow_definition_id' => $productId
                ? 'nullable|integer'
                : 'required|integer',

            'is_active' => 'nullable|boolean',

            'details' => 'required|array|min:1',

            'details.*.role_name' => $productId
                ? 'nullable|string|max:255'
                : 'required|string|max:255',
            'details.*.limit_amount' => $productId
                ? 'nullable|integer|min:1'
                : 'required|integer|min:1',
            'details.*.customer_type' => $productId
                ? 'nullable|string|max:255'
                : 'required|string|max:255',
            'details.*.is_active' => 'nullable|boolean',

        ];
    }
}
