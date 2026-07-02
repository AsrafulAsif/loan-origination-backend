<?php

namespace App\Http\Requests\ExternalApi;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExternalApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('api_method')) {
            $this->merge([
                'api_method' => strtoupper($this->input('api_method')),
            ]);
        }

        $this->merge([
            'is_active' => $this->input('is_active', true),
        ]);
    }


    public function rules(): array
    {
        $isStore = $this->isMethod('POST');

        return [
            'api_name' => [
                $isStore ? 'required' : 'sometimes',
                'string',
                'max:255',
            ],

            'api_code' => [
                $isStore ? 'required' : 'sometimes',
                'string',
                'max:255',
                Rule::unique('external_apis', 'api_code')
                    ->ignore($this->route('apiCode'), 'api_code'),
            ],

            'api_base_url' => [
                $isStore ? 'required' : 'sometimes',
                'string',
                'max:2048',
            ],

            'api_method' => [
                $isStore ? 'required' : 'sometimes',
                'string',
                Rule::in(['GET', 'POST', 'PUT', 'PATCH', 'DELETE']),
            ],

            'param_template' => ['nullable', 'string'],
            'request_template' => ['nullable', 'string'],
            'response_template' => ['nullable', 'string'],
            'headers_template' => ['nullable', 'string'],
            'created_by' => ['nullable', 'array'],
            'updated_by' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean' ],
        ];
    }

}
