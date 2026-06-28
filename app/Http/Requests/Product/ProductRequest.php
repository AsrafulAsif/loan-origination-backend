<?php

namespace App\Http\Requests\Product;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    public function rules(): array
    {
        $productId = $this->route('product_id');
        $isUpdate = $productId !== null;

        return [
            'product_name' => [
                $isUpdate ? 'sometimes' : 'required',
                'string',
                'max:255',
                Rule::unique('products', 'product_name')->ignore($productId), // 👈 correct ID
            ],

            'product_code' => [
                $isUpdate ? 'sometimes' : 'required',
                'string',
                'max:255',
            ],

            'product_type' => [
                $isUpdate ? 'sometimes' : 'required',
                'string',
                'max:255',
            ],

            'workflow_definition_id' => [
                $isUpdate ? 'sometimes' : 'required',
                'integer',
            ],
        ];
    }
}
