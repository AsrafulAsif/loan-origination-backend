<?php

namespace App\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

class LoanChatMessageRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'loan_application_id' => ['required', 'integer', 'exists:loan_applications,id'],
            'message' => ['required', 'string', 'max:10000'],
            'message_type' => ['required', 'string'],
            'from_employee_id' => ['nullable', 'string', 'exists:employees,id'],
            'from_stage' => ['required', 'string', 'exists:workflow_stages,id'],
            'reply_to' => ['nullable', 'integer', 'exists:loan_chat_messages,id'],
        ];
    }
}
