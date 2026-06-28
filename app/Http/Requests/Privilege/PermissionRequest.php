<?php

namespace App\Http\Requests\Privilege;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PermissionRequest extends FormRequest
{
    public function rules(): array
    {
        $permissionId = $this->route('permission_id');

        return [
            'permission_name' => [
                $permissionId ? 'nullable' : 'required',
                'string',
                'max:255',
            ],

            'permission_display_name' => [
                $permissionId ? 'nullable' : 'required',
                'string',
                'max:255',
            ],

            'permission_description' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'controller_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'api_urls' => [
                'nullable',
                'array',
            ],

            'api_urls.*.api_url' => [
                'required_with:api_urls',
                'string',
                'max:255',
            ],

            'api_urls.*.method_name' => [
                'required_with:api_urls',
                'string',
                'in:GET,POST,PUT,PATCH,DELETE',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],
            'permission_type_id' =>[
                'required',
                'integer',
            ]
        ];
    }
}
