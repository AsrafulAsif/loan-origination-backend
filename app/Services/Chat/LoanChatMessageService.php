<?php

namespace App\Services\Chat;

use App\Models\Chat\LoanChatMessages;
use App\Models\LoanOrigination\LoanApplication;
use App\Models\Workflow\WorkflowDefinition;
use App\Models\Workflow\WorkflowStage;
use App\Traits\UserSnapshotTrait;
use Exception;
use Illuminate\Support\Facades\DB;
use App\Services\LoanOrigination\LoanOriginationService;
use Throwable;

class LoanChatMessageService
{
    use UserSnapshotTrait;

    private LoanOriginationService $loanOriginationService;

    public function __construct(LoanOriginationService $loanOriginationService)
    {
        $this->loanOriginationService = $loanOriginationService;
    }

    /**
     * @throws Throwable
     */
    public function createMessage(array $data): LoanChatMessages
    {
        //TODO: Send email for every new message
        //TODO: file handling in the chat
        $loan_application = LoanApplication::findOrFail($data['loan_application_id']);
        $user = $this->getUserSnapshot();

        $userStageIds = $this->loanOriginationService->getAuthorizedStageIds($user['employee_id']);

        $formStageName = WorkflowStage::whereIn('id', $userStageIds)
            ->pluck('stage_name')->toArray();
        $formStageNameString = implode(', ', $formStageName);

        return DB::transaction(function () use ($formStageNameString, $user, $loan_application, $data) {
            return LoanChatMessages::create([
                'loan_application_id' => $data['loan_application_id'],
                'message' => $data['message'],
                'message_type' => $data['message_type'],
                'from_employee_id' => $user['employee_id'],
                'from_employee_name' => $user['full_name'],
                'from_stage' => $loan_application->current_workflow_stage_id,
                'from_stage_name' => $formStageNameString,
                'reply_to' => $data['reply_to'] ?? null,
                'is_active' => true,
                'created_by' => $user['employee_id'],
            ]);
        });
    }

    /**
     * @throws Throwable
     */
    public function loadMessage(int $loan_application_id): array
    {
        $user = $this->getUserSnapshot();
        $loan_application = LoanApplication::findOrFail($loan_application_id);

        //TODO first find the workflow array
        $workflow_definition = WorkflowDefinition::where('is_active', true)
            ->where('id', $loan_application->workflow_definition_id)
            ->firstOrFail();
        // TODO work flow array
        $workflow_definition_array = $workflow_definition->workflow_definition;

        $current_workflow_stage_id = $loan_application->current_workflow_stage_id;

        $authorizedStages = array_slice($workflow_definition_array, 0, array_search($current_workflow_stage_id, $workflow_definition_array) + 1);

        $loanStageIds = $this->loanOriginationService->getAuthorizedStageIds($user['employee_id']);

        $common = array_intersect($authorizedStages, $loanStageIds);

        if (empty($common)) {
            throw new Exception('Unauthorized: You are not authorized to view messages for this stage.');
        }

        $messages = LoanChatMessages::where('loan_application_id', $loan_application_id)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($message) {
                return $message
                    ->makeVisible(['created_at']) // ✅ make it visible
                    ->toArray();                 // ✅ then convert
            });

        // count unique participants
        $participantsCount = LoanChatMessages::where('loan_application_id', $loan_application_id)
            ->distinct('from_employee_id')
            ->count('from_employee_id');

        return [
            'messages' => $messages->toArray(),
            'total_participants' => $participantsCount,
        ];
    }

    /**
     * @throws Throwable
     */
    public function deleteMessage(int $id): void
    {
        DB::transaction(function () use ($id) {
            $message = LoanChatMessages::findOrFail($id);
            $message->delete();
        });
    }
}
