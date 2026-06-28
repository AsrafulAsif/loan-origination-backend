<?php

namespace App\Http\Controllers\Chat;

use App\Http\Requests\Chat\LoanChatMessageRequest;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\Chat\LoanChatMessageService;

class LoanChatMessagesController
{
    use ApiResponseTrait;

    protected LoanChatMessageService $messageService;

    public function __construct(LoanChatMessageService $messageService)
    {
        $this->messageService = $messageService;
    }

    /**
     * @throws \Throwable
     */
    public function createMessage(LoanChatMessageRequest $request): JsonResponse
    {
        $message = $this->messageService->createMessage(
            $request->validated()
        );
        return $this->successResponse($message, 'Message created successfully', 201);
    }


    public function deleteMessage(int $id): JsonResponse
    {
        $this->messageService->deleteMessage($id);

        return $this->successResponse(null, 'Message deleted successfully', 200);
    }


    /**
     * @throws \Exception
     */
    public function loadMessages(int $loan_application_id): JsonResponse
    {
        $message = $this->messageService->loadMessage($loan_application_id);
        return $this->successResponse($message, 'Message loaded successfully', 200);
    }
}
