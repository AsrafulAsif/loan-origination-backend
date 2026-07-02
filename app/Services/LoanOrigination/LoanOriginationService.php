<?php

namespace App\Services\LoanOrigination;

use App\Enum\AssetProductType;
use App\Models\Auth\ApiUser;
use App\Models\LoanOrigination\LoanApplication;
use App\Models\LoanOrigination\LoanApplicationDetail;
use App\Models\LoanOrigination\LoanApplicationFieldResponses;
use App\Models\LoanOrigination\LoanApplicationGroupResponseInstances;
use App\Models\LoanOrigination\LoanApplicationGroupResponses;
use App\Models\LoanOrigination\LoanApplicationWorkflowLog;
use App\Models\Privilege\Role;
use App\Models\Product\Product;
use App\Models\Template\FieldGroups;
use App\Models\Template\Fields;
use App\Models\Template\Sections;
use App\Models\Workflow\WorkflowDefinition;
use App\Models\Workflow\WorkflowStage;
use App\Traits\UserSnapshotTrait;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class LoanOriginationService
{
    use UserSnapshotTrait;

    protected int $page;
    protected int $perPage;

    protected string $hqCode;

    public function __construct()
    {
        $this->page     = config('app.default_page');
        $this->perPage  = config('app.default_per_page');
        $this->hqCode   = config('app.hq_code');
    }


    private function computeLoanStatus(
        string $action,
        ?int   $actingStageId,
        ?int   $nextStageId
    ): string
    {
        if ($action === 'DRAFT') {
            return 'DRAFT';
        }

        $actingStage = WorkflowStage::find($actingStageId);
        $actingCode = $actingStage?->stage_code ?? 'UNKNOWN';

        if ($action === 'REJECT') {
            return $actingCode . '_REJECTED';
        }

        if ($action === 'REVERT') {
            return $actingCode . '_REVERTED';
        }

        if ($action === 'SUBMIT' || $action === 'APPROVE') {
            if ($actingStageId === $nextStageId) {
                return 'APPROVED';
            }
            $nextStage = WorkflowStage::find($nextStageId);
            $nextCode = $nextStage?->stage_code ?? 'UNKNOWN';

            return $nextCode . '_PENDING';
        }

        return 'UNKNOWN';
    }

    public function initLoanOrigination(string $productType) : array
    {
        $loanOriginationId = $this->createLoanOriginationId($productType);

        return [
            'loanOriginationId' => $loanOriginationId,
            'loan_id' => $loanOriginationId,
        ];
    }



    /** @throws Throwable */
    public function createLoanForDraft(array $data): string
    {
        return $this->submitOrCreateLoan($data, 'DRAFT');
    }

    /** @throws Throwable */
    public function createLoanForSubmit(array $data): string
    {
        return $this->submitOrCreateLoan($data, 'SUBMITTED');
    }


    /** @throws Throwable */
    private function createLoan(array $data, string $maker_status, ApiUser $user): string
    {
        $loan_id = $data['loan_id'] ?? $this->getLoanId();

        try {
            $product = Product::where('is_active', true)
                ->where('id', $data['product_id'])
                ->firstOrFail();

            $workflow_definition = WorkflowDefinition::where('is_active', true)
                ->where('id', $product->workflow_definition_id)
                ->firstOrFail();

            $workflow_definition_array = $workflow_definition->workflow_definition;
            $current_workflow_stage_id = $workflow_definition_array[0];
            $isDraft = ($maker_status === 'DRAFT');

            $next_workflow_stage_id = $isDraft
                ? $current_workflow_stage_id
                : $this->getNextWorkFlowStage($workflow_definition_array, $current_workflow_stage_id);

            $loan_status = $this->computeLoanStatus(
                action: $isDraft ? 'DRAFT' : 'SUBMIT',
                actingStageId: $current_workflow_stage_id,
                nextStageId: $isDraft ? null : $next_workflow_stage_id,
            );

            $remarks = $data['remarks'] ?? '';

            DB::transaction(function () use (
                $loan_id, $data, $workflow_definition,
                $current_workflow_stage_id, $next_workflow_stage_id,
                $user, $maker_status, $loan_status, $remarks
            ) {
                $loan_application = LoanApplication::create([
                    'loan_id' => $loan_id,
                    'product_id' => $data['product_id'],
                    'form_template_id' => $data['form_template_id'],      // ← fixed key
                    'workflow_definition_id' => $workflow_definition->id,
                    'maker_status' => $maker_status,
                    'current_workflow_stage_id' => $next_workflow_stage_id,
                    'reached_ho' => $this->isHoStage($next_workflow_stage_id),
                    'current_status' => $loan_status,
                    'created_by' => $this->getUserSnapshot(),
                    'branch_code' => $user->orbit_branch_code,
                ]);

                LoanApplicationDetail::create([
                    'loan_application_id' => $loan_application->id,
                    'data_json' => $data['data_json'],    // ← nested payload only
                    'version' => 1,
                    'is_active' => true,
                ]);


                if ($maker_status === 'SUBMITTED') {
                    $this->syncFieldResponses(
                        $loan_application->id,
                        $data['form_template_id'],          // ← fixed key
                        $data['data_json']                  // ← pass the nested data_json
                    );

                    $this->addLoanApplicationLogStageHistory(
                        loan_application_id: $loan_application->id,
                        form_stage: $current_workflow_stage_id,
                        to_stage: $next_workflow_stage_id,
                        stage_status: $loan_status,
                        remarks: $remarks ?: 'Branch Maker Submitted',
                        action_type: 'SUBMIT',
                    );
                }
            });

        } catch (Throwable $e) {
            Log::error($e);
            throw $e;
        }

        return $loan_id;
    }

    /** @throws Throwable */
    private function submitOrCreateLoan(array $data, string $maker_status): string
    {
        try {
            $user = auth()->user();
            $loan_id = $data['loan_id'] ?? null;

            if ($loan_id) {
                $loan_application = LoanApplication::where('loan_id', $loan_id)->first();

                if (!$loan_application) {
                    return $this->createLoan($data, $maker_status, $user);
                }

                $loan_application_detail = LoanApplicationDetail::where(
                    'loan_application_id', $loan_application->id
                )->firstOrFail();

                $workflow_definition_array = WorkflowDefinition::where('is_active', true)
                    ->where('id', $loan_application->workflow_definition_id)
                    ->firstOrFail()
                    ->workflow_definition;

                $current_workflow_stage_id = $loan_application->current_workflow_stage_id;

                $authorizedStageIds = $this->getAuthorizedStageIds($user->employee_id);

                if (!in_array($current_workflow_stage_id, $authorizedStageIds)) {
                    abort(403, 'You are not authorized to review this stage.');
                }

                $isDraft = ($maker_status === 'DRAFT');

                $next_workflow_stage_id = $isDraft
                    ? $current_workflow_stage_id
                    : $this->getNextWorkFlowStage($workflow_definition_array, $current_workflow_stage_id);

                $loan_status = $this->computeLoanStatus(
                    action: $isDraft ? 'DRAFT' : 'SUBMIT',
                    actingStageId: $current_workflow_stage_id,
                    nextStageId: $isDraft ? null : $next_workflow_stage_id,
                );

                DB::transaction(function () use (
                    $next_workflow_stage_id, $current_workflow_stage_id,
                    $loan_application, $loan_application_detail,
                    $data, $maker_status, $loan_status
                ) {
                    $loan_application->update([
                        'current_workflow_stage_id' => $next_workflow_stage_id,
                        'reached_ho' => $this->hasReachedHo(
                            $loan_application,
                            $current_workflow_stage_id,
                            $next_workflow_stage_id
                        ),
                        'maker_status' => $maker_status,
                        'current_status' => $loan_status,
                        'created_by' => $this->getUserSnapshot(),
                    ]);

                    $loan_application_detail->update([
                        'loan_application_id' => $loan_application->id,
                        'data_json' => $data['data_json'],   // ← nested payload only
                        'version' => 1,
                        'is_active' => true,
                    ]);

                    if ($maker_status === 'SUBMITTED') {
                        $this->syncFieldResponses(
                            $loan_application->id,
                            $data['form_template_id'],      // ← top-level key
                            $data['data_json']              // ← pass the nested data_json
                        );

                        $this->addLoanApplicationLogStageHistory(
                            loan_application_id: $loan_application->id,
                            form_stage: $current_workflow_stage_id,
                            to_stage: $next_workflow_stage_id,
                            stage_status: $loan_status,
                            remarks: $data['remarks'] ?? 'Maker Submitted after Edit',
                            action_type: 'SUBMIT',
                        );
                    }
                });

                return $loan_application->loan_id;

            } else {
                return $this->createLoan($data, $maker_status, $user);
            }

        } catch (Throwable $e) {
            Log::error($e->getMessage());
            throw $e;
        }
    }



    public function reviewLoan(array $data): void
    {
        try {
            $user = auth()->user();
            $action = strtoupper($data['action'] ?? '');

            if (!in_array($action, ['APPROVE', 'REVERT', 'REJECT'])) {
                abort(422, 'Invalid action. Must be APPROVE, REVERT or REJECT.');
            }

            $loan_application = LoanApplication::where('loan_id', $data['loan_id'])->firstOrFail();

            $workflow_definition_array = WorkflowDefinition::where('is_active', true)
                ->where('id', $loan_application->workflow_definition_id)
                ->firstOrFail()
                ->workflow_definition;

            $current_workflow_stage_id = $loan_application->current_workflow_stage_id;

            $authorizedStageIds = $this->getAuthorizedStageIds($user->employee_id);

            if (!in_array($current_workflow_stage_id, $authorizedStageIds)) {
                abort(403, 'You are not authorized to review this stage.');
            }

            // ── Branch Manager stage guard ────────────────────────────────────────
            $currentStage = WorkflowStage::find($current_workflow_stage_id);

            if ($currentStage?->stage_type === 'BRANCH') {
                $visibleBranchCodes = ApiUser::query()
                    ->where(function ($q) use ($user) {
                        $q->where('reporting_branch_code', $user->orbit_branch_code)
                            ->orWhere('reporting_branch_code2', $user->orbit_branch_code);
                    })
                    ->pluck('orbit_branch_code')
                    ->unique()
                    ->push($user->orbit_branch_code)
                    ->toArray();

                if (!in_array($loan_application->branch_code, $visibleBranchCodes)) {
                    abort(403, 'You are not authorized to review loans from this branch.');
                }
            }


            // ── Pick guard ────────────────────────────────────────────────────────
            $assignedTo = is_array($loan_application->assigned_to)
                ? $loan_application->assigned_to
                : json_decode($loan_application->assigned_to, true);

            if (!empty($assignedTo)) {
                if (($assignedTo['employee_id'] ?? null) !== $user->employee_id) {
                    abort(403, 'This loan has been picked by someone else. Only the assigned reviewer can act on it.');
                }
            }
            // ─────────────────────────────────────────────────────────────────────

            $defaultRemarks = [
                'APPROVE'  => 'Approved',
                'REVERT'   => 'Sent back for correction',
                'REJECT'   => 'Rejected',
            ];

            $remarks = !empty(trim($data['remarks'] ?? ''))
                ? trim($data['remarks'])
                : $defaultRemarks[$action];

            match ($action) {
                'APPROVE' => $this->handleApprove(
                    $loan_application,
                    $workflow_definition_array,
                    $current_workflow_stage_id,
                    $remarks
                ),
                'REVERT' => $this->handleRevert($data),
                'REJECT' => $this->handleReject(
                    $loan_application,
                    $current_workflow_stage_id,
                    $remarks
                ),
            };

        } catch (Throwable $e) {
            Log::error($e->getMessage());
            throw $e;
        }
    }

    /**
     * @throws Throwable
     */
    private function handleApprove(
        LoanApplication $loan_application,
        array           $workflow_definition_array,
        int             $current_workflow_stage_id,
        string          $remarks
    ): void
    {
        $next_workflow_stage_id = $this->getNextWorkFlowStage(
            $workflow_definition_array,
            $current_workflow_stage_id
        );

        $approved_status = $this->computeLoanStatus(
            action: 'APPROVE',
            actingStageId: $current_workflow_stage_id,
            nextStageId: $next_workflow_stage_id,
        );

        DB::transaction(function () use (
            $loan_application, $current_workflow_stage_id,
            $next_workflow_stage_id, $approved_status, $remarks
        ) {
            $loan_application->update([
                'current_workflow_stage_id' => $next_workflow_stage_id,
                'reached_ho' => $this->hasReachedHo(
                    $loan_application,
                    $current_workflow_stage_id,
                    $next_workflow_stage_id
                ),
                'current_status' => $approved_status,
                'assigned_to' => null,   // ← clear on stage move
                'updated_by' => $this->getUserSnapshot(),
            ]);

            $this->addLoanApplicationLogStageHistory(
                loan_application_id: $loan_application->id,
                form_stage: $current_workflow_stage_id,
                to_stage: $next_workflow_stage_id,
                stage_status: $approved_status,
                remarks: $remarks,
                action_type: 'APPROVE',
            );
        });
    }



    private function handleRevert(array $data): void
    {
        $loan_application = LoanApplication::where('loan_id', $data['loan_id'])->firstOrFail();

        $send_back_config = $data['send_back_configuration'];
        $current_stage_id = $send_back_config['current_stage_id'];
        $next_stage_id = $send_back_config['target_stage_id'] ?? $send_back_config['next_stage_id'] ?? null;
        $assigned_to = $data['assigned_to'] ?? null;

        $revert_status = $this->computeLoanStatus(
            action: 'REVERT',
            actingStageId: $current_stage_id,
            nextStageId: $next_stage_id,
        );

        $remarks = $data['remarks'] ?? '';

        DB::transaction(function () use (
            $loan_application,
            $current_stage_id,
            $next_stage_id,
            $revert_status,
            $remarks,
            $assigned_to,
            $send_back_config,
        ) {
            $loan_application->update([
                'current_workflow_stage_id' => $next_stage_id,
                'reached_ho' => $this->hasReachedHo(
                    $loan_application,
                    $current_stage_id,
                    $next_stage_id
                ),
                'current_status' => $revert_status,
                'assigned_to' => $assigned_to,
                'updated_by' => $this->getUserSnapshot(),
                'reverted' => 1,
            ]);

            $this->addLoanApplicationLogStageHistory(
                loan_application_id: $loan_application->id,
                form_stage: $current_stage_id,
                to_stage: $next_stage_id,
                stage_status: $revert_status,
                remarks: $remarks,
                action_type: 'REVERT',
                send_back_config: $send_back_config,
                revert_pending: 1
            );
        });
    }

    /**
     * @throws Throwable
     */
    private function handleReject(
        LoanApplication $loan_application,
        int             $current_workflow_stage_id,
        string          $remarks
    ): void
    {
        $reject_status = $this->computeLoanStatus(
            action: 'REJECT',
            actingStageId: $current_workflow_stage_id,
            nextStageId: null,
        );

        DB::transaction(function () use (
            $loan_application, $current_workflow_stage_id,
            $reject_status, $remarks
        ) {
            $loan_application->update([
                'current_workflow_stage_id' => $current_workflow_stage_id,
                'reached_ho' => $this->hasReachedHo($loan_application, $current_workflow_stage_id),
                'current_status' => $reject_status,
                'maker_status' => 'LOCK',
                'assigned_to' => null,   // ← clear on stage move
                'updated_by' => $this->getUserSnapshot(),
            ]);

            $this->addLoanApplicationLogStageHistory(
                loan_application_id: $loan_application->id,
                form_stage: $current_workflow_stage_id,
                to_stage: $current_workflow_stage_id,
                stage_status: $reject_status,
                remarks: $remarks,
                action_type: 'REJECT',
            );
        });
    }


    /** @throws Throwable */
    public function pickLoan(array $data): void
    {
        try {
            $user = auth()->user();

            $loan_application = LoanApplication::where('loan_id', $data['loan_id'])->firstOrFail();

            $authorizedStageIds = $this->getAuthorizedStageIds($user->employee_id);

            if (!in_array($loan_application->current_workflow_stage_id, $authorizedStageIds)) {
                abort(403, 'You are not authorized to pick loans at this stage.');
            }

            if ($loan_application->maker_status !== 'SUBMITTED') {
                abort(422, 'Only submitted loans can be picked.');
            }

            if (!empty($loan_application->assigned_to)) {
                abort(422, 'This loan has already been picked.');
            }

            $pickedStatus = $this->buildPickedStatus($loan_application->current_status);

            DB::transaction(function () use ($loan_application, $pickedStatus) {
                $loan_application->update([
                    'assigned_to' => $this->getUserSnapshot(),
                    'current_status' => $pickedStatus,
                    'updated_by' => $this->getUserSnapshot(),
                ]);

                $this->addLoanApplicationLogStageHistory(
                    loan_application_id: $loan_application->id,
                    form_stage: $loan_application->current_workflow_stage_id,
                    to_stage: $loan_application->current_workflow_stage_id,
                    stage_status: $pickedStatus,
                    remarks: 'Form picked for review',
                    action_type: 'PICK',
                );
            });

        } catch (Throwable $e) {
            Log::error($e->getMessage());
            throw $e;
        }
    }


    /** @throws Throwable */
    public function assignLoan(array $data): void
    {
        try {
            $user = auth()->user();

            $loan_application = LoanApplication::where('loan_id', $data['loan_id'])->firstOrFail();

            // Manager must be authorized at this stage
            $authorizedStageIds = $this->getAuthorizedStageIds($user->employee_id);

            if (!in_array($loan_application->current_workflow_stage_id, $authorizedStageIds)) {
                abort(403, 'You are not authorized to assign loans at this stage.');
            }

            if ($loan_application->maker_status !== 'SUBMITTED') {
                abort(422, 'Only submitted loans can be assigned.');
            }

            // Resolve assignee
            $assignee = ApiUser::where('employee_id', $data['employee_id'])
                ->where('emp_status', 'Active')
                ->firstOrFail();

            // Assignee must also be authorized at this stage via their roles
            $assigneeStageIds = $this->getAuthorizedStageIds($assignee->employee_id);

            if (!in_array($loan_application->current_workflow_stage_id, $assigneeStageIds)) {
                abort(422, 'The selected user is not authorized at this stage.');
            }

            $pickedStatus = $this->buildPickedStatus($loan_application->current_status);

            $remarks = !empty(trim($data['remarks'] ?? ''))
                ? trim($data['remarks'])
                : "Assigned to {$assignee->name} by {$user->name}";

            DB::transaction(function () use (
                $loan_application, $assignee, $pickedStatus, $remarks
            ) {
                $loan_application->update([
                    'assigned_to'     => $this->getUserSnapshotFromObj($assignee),
                    'current_status'  => $pickedStatus,
                    'updated_by'      => $this->getUserSnapshot(),
                ]);

                $this->addLoanApplicationLogStageHistory(
                    loan_application_id: $loan_application->id,
                    form_stage: $loan_application->current_workflow_stage_id,
                    to_stage: $loan_application->current_workflow_stage_id,
                    stage_status: $pickedStatus,
                    remarks: $remarks,
                    action_type: 'PICK',
                );
            });

        } catch (Throwable $e) {
            Log::error($e->getMessage());
            throw $e;
        }
    }

    public function getCurrentStageUsers(string $loanId, array $filters): LengthAwarePaginator
    {
        $page = $filters['page'] ?? $this->page;
        $perPage = $filters['per_page'] ?? $this->perPage;
        $search = isset($filters['search']) ? trim((string)$filters['search']) : null;

        $loanApplication = LoanApplication::where('loan_id', $loanId)->firstOrFail();
        $currentStageId = (int)$loanApplication->current_workflow_stage_id;

        $stage = WorkflowStage::query()
            ->select(['id', 'stage_code', 'stage_name', 'stage_type'])
            ->findOrFail($currentStageId);

        $roleIds = DB::connection('mysql')
            ->table('role_stages')
            ->where('stage_id', $currentStageId)
            ->where('is_active', true)
            ->pluck('role_id')
            ->unique()
            ->values()
            ->toArray();

        if (empty($roleIds)) {
            return new LengthAwarePaginator([], 0, $perPage, $page);
        }

        $employeeIds = DB::connection('mysql')
            ->table('user_roles')
            ->whereIn('role_id', $roleIds)
            ->where('is_active', true)
            ->pluck('employee_id')
            ->unique()
            ->values()
            ->toArray();

        if (empty($employeeIds)) {
            return new LengthAwarePaginator([], 0, $perPage, $page);
        }

        $paginator = ApiUser::query()
            ->select([
                'employee_id',
                'full_name',
                'email_address',
                'mobile_no',
                'orbit_branch_code',
                'orbit_branch_name',
                'desig_name',
                'division_name',
                'emp_status',
            ])
            ->whereIn('employee_id', $employeeIds)
            ->where('emp_status', 'Active')
            ->when($search, function ($query, string $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('employee_id', 'like', "%{$search}%")
                        ->orWhere('full_name', 'like', "%{$search}%")
                        ->orWhere('email_address', 'like', "%{$search}%")
                        ->orWhere('mobile_no', 'like', "%{$search}%")
                        ->orWhere('desig_name', 'like', "%{$search}%");
                });
            })
            ->orderBy('full_name')
            ->paginate(perPage: $perPage, page: $page);

        $pageEmployeeIds = collect($paginator->items())->pluck('employee_id')->all();

        $roles = Role::query()
            ->join('user_roles', 'roles.id', '=', 'user_roles.role_id')
            ->whereIn('user_roles.employee_id', $pageEmployeeIds)
            ->whereIn('user_roles.role_id', $roleIds)
            ->where('user_roles.is_active', true)
            ->where('roles.is_active', true)
            ->orderBy('roles.id')
            ->select([
                'roles.id',
                'roles.role_name',
                'roles.role_display_name',
                'user_roles.employee_id',
            ])
            ->get()
            ->groupBy('employee_id');

        $paginator->getCollection()->transform(function ($user) use ($roles, $stage) {
            return [
                'employee_id' => $user->employee_id,
                'full_name' => $user->full_name,
                'email_address' => $user->email_address,
                'mobile_no' => $user->mobile_no,
                'branch_code' => $user->orbit_branch_code,
                'branch_name' => $user->orbit_branch_name,
                'designation' => $user->desig_name,
                'division' => $user->division_name,
                'emp_status' => $user->emp_status,
                'current_stage' => [
                    'id' => $stage->id,
                    'stage_code' => $stage->stage_code,
                    'stage_name' => $stage->stage_name,
                    'stage_type' => $stage->stage_type,
                ],
                'roles' => $roles->get($user->employee_id, collect())->values(),
            ];
        });

        return $paginator;
    }


    private function getLoanId(): string
    {
        $uniquePart = uniqid('', true);
        $datePart = date('d.m.Y');
        return 'LO' . $datePart . '.' . strtoupper(substr($uniquePart, -5));
    }

    private function getNextWorkFlowStage(array $workflow_definition, int $currentStage): int
    {
        $index = array_search($currentStage, $workflow_definition);
        return ($index !== false && isset($workflow_definition[$index + 1]))
            ? $workflow_definition[$index + 1]
            : $currentStage;
    }

    private function hasReachedHo(?LoanApplication $loanApplication, ?int ...$stageIds): bool
    {
        if ($loanApplication?->reached_ho) {
            return true;
        }

        foreach ($stageIds as $stageId) {
            if ($this->isHoStage($stageId)) {
                return true;
            }
        }

        return false;
    }

    private function isHoStage(?int $stageId): bool
    {
        if (!$stageId) {
            return false;
        }

        $stageType = WorkflowStage::whereKey($stageId)->value('stage_type');

        return strtoupper((string) $stageType) === 'HO';
    }

    /**
     * Write one row to the workflow-log table.
     *
     * @param string $action_type DRAFT | SUBMIT | APPROVE | REVERT | REJECT
     *
     * @throws Throwable
     */
    private function addLoanApplicationLogStageHistory(
        int    $loan_application_id,
        int    $form_stage,
        ?int   $to_stage,
        string $stage_status = '',
        string $remarks = '',
        string $action_type = 'SUBMIT',
        ?array $send_back_config = [],
        ?int   $revert_pending = null
    ): void
    {
        LoanApplicationWorkflowLog::create([
            'loan_application_id' => $loan_application_id,
            'from_stage_id' => $form_stage,
            'to_stage_id' => $to_stage,
            'stage_status' => $stage_status,
            'action_type' => $action_type,
            'remarks' => $remarks,
            'send_back_config' => $send_back_config,
            'action_by' => $this->getUserSnapshot(),
            'action_at' => now(),
            'revert_pending' => $revert_pending,
        ]);
    }


    /**
     * Strip any existing _PICKED suffix then re-append,
     * so reassignment never double-appends.
     * e.g. "HO_RETAIL_PENDING" → "HO_RETAIL_PENDING_PICKED"
     *      "HO_RETAIL_PENDING_PICKED" → "HO_RETAIL_PENDING_PICKED"  (idempotent)
     */
    private function buildPickedStatus(string $current_status): string
    {
        $base = str_ends_with($current_status, '_PICKED')
            ? substr($current_status, 0, -7)   // strip "_PICKED"
            : $current_status;

        return $base . '_PICKED';
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FIELD / GROUP SYNC
    // ─────────────────────────────────────────────────────────────────────────

    private function getTemplateFieldMap(int $form_template_id): array
    {
        return Fields::query()
            ->join('sections', 'fields.section_id', '=', 'sections.id')
            ->where('sections.template_id', $form_template_id)
            ->where('fields.enabled', true)
            ->select('fields.*')
            ->get()
            ->keyBy('field_key')
            ->all();
    }

    private function getTemplateGroupMap(int $form_template_id): array
    {
        return FieldGroups::query()
            ->join('sections', 'field_groups.section_id', '=', 'sections.id')
            ->where('sections.template_id', $form_template_id)
            ->where('field_groups.enabled', true)
            ->select('field_groups.*')
            ->get()
            ->keyBy('group_key')
            ->all();
    }

    private function syncFieldResponses(int $loan_application_id, int $form_template_id, array $data_json): void
    {
        // ── Pre-load lookup maps for this form template ──────────────────────────
        // Fields/FieldGroups don't store form_template_id directly — they hang off
        // Sections via section_id, and Sections holds form_template_id. So we join
        // through sections to scope the maps to this specific template.
        $fieldIdMap = Fields::join('sections', 'fields.section_id', '=', 'sections.id')
            ->where('sections.template_id', $form_template_id)
            ->pluck('fields.id', 'fields.field_key')
            ->all();

        $groupIdMap = FieldGroups::join('sections', 'field_groups.section_id', '=', 'sections.id')
            ->where('sections.template_id', $form_template_id)
            ->pluck('field_groups.id', 'field_groups.group_key')
            ->all();

        LoanApplicationFieldResponses::where('loan_application_id', $loan_application_id)
            ->update(['is_active' => false]);

        LoanApplicationGroupResponses::where('loan_application_id', $loan_application_id)
            ->update(['is_active' => false]);

        foreach ($data_json['sections'] as $section) {

            foreach ($section['fields'] as $field_key => $value) {
                LoanApplicationFieldResponses::create([
                    'loan_application_id' => $loan_application_id,
                    'field_key' => $field_key,
                    'field_id' => $fieldIdMap[$field_key] ?? null,
                    'group_instance_id' => null,
                    'value_json' => json_encode($value),
                    'is_valid' => true,
                    'errors' => null,
                    'is_active' => true,
                ]);
            }

            foreach ($section['fieldGroups'] as $group) {
                $group_key = $group['groupKey'];

                $groupResponse = LoanApplicationGroupResponses::create([
                    'loan_application_id' => $loan_application_id,
                    'group_key' => $group_key,
                    'group_id' => $groupIdMap[$group_key] ?? null,
                    'is_active' => true,
                ]);

                foreach ($group['instances'] as $instance_index => $instance) {
                    $instance_key = $instance['instanceId'];
                    $instanceFields = $instance['fields'];

                    $groupInstance = LoanApplicationGroupResponseInstances::create([
                        'group_response_id' => $groupResponse->id,
                        'instance_index' => $instance_index,
                        'instance_key' => $instance_key,
                        'is_active' => true,
                    ]);

                    foreach ($instanceFields as $field_key => $value) {
                        LoanApplicationFieldResponses::create([
                            'loan_application_id' => $loan_application_id,
                            'field_key' => $field_key,
                            'field_id' => $fieldIdMap[$field_key] ?? null,
                            'group_instance_id' => $groupInstance->id,
                            'value_json' => json_encode($value),
                            'is_valid' => true,
                            'errors' => null,
                            'is_active' => true,
                        ]);
                    }
                }
            }
        }
    }


    public function getDashboardLoans(array $data): LengthAwarePaginator
    {
        $user = auth()->user();
        $page = $data['page'] ?? $this->page;
        $perPage = $data['per_page'] ?? $this->perPage;

        $isHQ = $user->orbit_branch_code === $this->hqCode;
        $isBranchManager = blank($user->reporting_branch_code)
            && blank($user->reporting_branch_code2)
            && !$isHQ;

        // Authorized stage IDs for this user (empty if maker with no roles)
        $authorizedStageIds = $this->getAuthorizedStageIds($user->employee_id);
        $hasStageRole = !empty($authorizedStageIds);

        $query = $this->loanListQuery()
            ->whereNull('loan_applications.deleted_at')
            ->where('loan_applications.maker_status', 'SUBMITTED');

        $this->applyLoanListFilters($query, $data);

        if ($isHQ) {
            // HQ (RRM, CAD etc.)
            // Only loans sitting at their authorized stages — no branch filter
            $query->whereIn('loan_applications.current_workflow_stage_id', $authorizedStageIds);

        } elseif ($isBranchManager && $hasStageRole) {
            // Branch Manager
            // Loans at their authorized stage within their branch + sub-branches
            $subBranchCodes = ApiUser::query()
                ->where(function ($q) use ($user) {
                    $q->where('reporting_branch_code', $user->orbit_branch_code)
                        ->orWhere('reporting_branch_code2', $user->orbit_branch_code);
                })
                ->pluck('orbit_branch_code')
                ->unique()
                ->toArray();

            $visibleBranchCodes = array_unique(
                array_merge([$user->orbit_branch_code], $subBranchCodes)
            );

            $query->whereIn('loan_applications.current_workflow_stage_id', $authorizedStageIds)
                ->whereIn('loan_applications.branch_code', $visibleBranchCodes);

        } else {
            // Sub-branch maker (no stage role)
            // All submitted loans in their own branch only
            $query->where('loan_applications.branch_code', $user->orbit_branch_code);
        }

        return $this->paginateLoanList(
            $query
                ->orderBy('loan_applications.created_at', 'desc')
                ->paginate(perPage: $perPage, page: $page)
        );
    }

    public function getAllLoansCreatedByMe(array $data): LengthAwarePaginator
    {
        $user = auth()->user();
        $page = $data['page'] ?? $this->page;
        $perPage = $data['per_page'] ?? $this->perPage;

        // Application rows this user reviewed (acted on in workflow log)
        $reviewedApplicationIds = DB::table('loan_application_workflow_logs')
            ->where('action_by->employee_id', $user->employee_id)
            ->pluck('loan_application_id')
            ->unique()
            ->toArray();

        $query = $this->loanListQuery()
            ->whereNull('loan_applications.deleted_at')
            ->where(function ($q) use ($user, $reviewedApplicationIds) {

                // Loans I created
                $q->where('loan_applications.created_by->employee_id', $user->employee_id);

                // Loans I reviewed
                if (!empty($reviewedApplicationIds)) {
                    $q->orWhereIn('loan_applications.id', $reviewedApplicationIds);
                }
            });

        $this->applyLoanListFilters($query, $data);

        return $this->paginateLoanList(
            $query
                ->orderBy('loan_applications.created_at', 'desc')
                ->paginate(perPage: $perPage, page: $page)
        );
    }

    public function getHoReachedLoans(array $data): LengthAwarePaginator
    {
        $page = $data['page'] ?? $this->page;
        $perPage = $data['per_page'] ?? $this->perPage;

        $query = $this->loanListQuery()
            ->whereNull('loan_applications.deleted_at')
            ->where('loan_applications.reached_ho', true);

        $this->applyLoanListFilters($query, $data);

        return $this->paginateLoanList(
            $query
                ->orderBy('loan_applications.updated_at', 'desc')
                ->paginate(perPage: $perPage, page: $page)
        );
    }

    private function loanListQuery(): Builder
    {
        return DB::table('loan_applications')
            ->join('loan_application_details', function ($join) {
                $join->on('loan_applications.id', '=', 'loan_application_details.loan_application_id')
                    ->where('loan_application_details.is_active', true);
            })
            ->join('products', 'loan_applications.product_id', '=', 'products.id')
            ->join('workflow_stages', 'loan_applications.current_workflow_stage_id', '=', 'workflow_stages.id')
            ->select(
                'loan_applications.id',
                'loan_applications.loan_id',
                'loan_applications.product_id',
                'loan_applications.form_template_id',
                'loan_applications.workflow_definition_id',
                'loan_applications.current_workflow_stage_id',
                'loan_applications.reached_ho',
                'loan_applications.current_status',
                'loan_applications.assigned_to',
                'loan_applications.branch_code',
                'loan_applications.created_by',
                'loan_applications.updated_by',
                'loan_applications.maker_status',
                'loan_applications.created_at',
                'loan_applications.updated_at',
                'products.product_name',
                'products.product_code',
                'products.product_type',
                'workflow_stages.stage_code',
                'workflow_stages.stage_name',
                'workflow_stages.stage_type',
            );
    }

    private function applyLoanListFilters(Builder $query, array $filters): void
    {
        $exactFilters = [
            'loan_id' => 'loan_applications.loan_id',
            'product_name' => 'products.product_name',
            'product_code' => 'products.product_code',
            'branch_code' => 'loan_applications.branch_code',
        ];

        foreach ($exactFilters as $filterKey => $column) {
            if (!empty($filters[$filterKey])) {
                $query->where($column, $filters[$filterKey]);
            }
        }

        if (!empty($filters['created_by_email'])) {
            $query->where('loan_applications.created_by->email_address', $filters['created_by_email']);
        }

        if (!empty($filters['assigned_to_email'])) {
            $query->where('loan_applications.assigned_to->email_address', $filters['assigned_to_email']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('loan_applications.created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('loan_applications.created_at', '<=', $filters['date_to']);
        }
    }

    private function paginateLoanList(LengthAwarePaginator $paginator): LengthAwarePaginator
    {
        $paginator->getCollection()->transform(function ($item) {
            $item->created_by = json_decode($item->created_by);
            $item->updated_by = json_decode($item->updated_by);
            $item->assigned_to = json_decode($item->assigned_to);
            $item->reached_ho = (bool) $item->reached_ho;
            return $item;
        });

        return $paginator;
    }


    private function buildWorkflowHistory(int $loan_application_id): array
    {
        // 1. Fetch all log rows ordered chronologically
        $logs = LoanApplicationWorkflowLog::where('loan_application_id', $loan_application_id)
            ->orderBy('action_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        if ($logs->isEmpty()) {
            return [];
        }

        // 2. Collect all stage IDs that appear in any log row
        $stageIds = $logs
            ->flatMap(fn($log) => [$log->from_stage_id, $log->to_stage_id])
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        // 3. Load stage meta in one query and index by id
        $stageMap = WorkflowStage::whereIn('id', $stageIds)
            ->get(['id', 'stage_code', 'stage_name'])
            ->keyBy('id');

        // 4. Build the history array
        $lastIndex = $logs->count() - 1;

        return $logs->map(function ($log, $index) use ($stageMap, $lastIndex) {

            $fromStage = $log->from_stage_id ? $stageMap->get($log->from_stage_id) : null;
            $toStage = $log->to_stage_id ? $stageMap->get($log->to_stage_id) : null;

            // action_by is cast to array in the model
            $actionBy = is_array($log->action_by)
                ? $log->action_by
                : json_decode($log->action_by, true);

            [$uiLabel, $uiColor] = $this->resolveUiMeta(
                $log->action_type ?? 'SUBMIT',
                $log->stage_status,
                $toStage?->stage_code
            );

            return [
                'id' => $log->id,
                'action_type' => $log->action_type ?? 'SUBMIT',
                'stage_status' => $log->stage_status,

                'from_stage' => $fromStage ? [
                    'id' => $fromStage->id,
                    'code' => $fromStage->stage_code,
                    'name' => $fromStage->stage_name,
                ] : null,

                'to_stage' => $toStage ? [
                    'id' => $toStage->id,
                    'code' => $toStage->stage_code,
                    'name' => $toStage->stage_name,
                ] : null,

                'action_by' => $actionBy,
                'action_at' => $log->action_at?->toISOString(),
                'remarks' => $log->remarks ?? '',
                'is_current' => ($index === $lastIndex),
                // UI presentation hints (the frontend can use these directly)
                'ui_label' => $uiLabel,
                'ui_color' => $uiColor,
            ];
        })->toArray();
    }

    public function getLoanById(string $loan_id): array
    {
        $loanApplication = LoanApplication::where('loan_id', $loan_id)->firstOrFail();

        // ── Workflow history ─────────────────────────────────────────────────────
        $workflow_log = $this->buildWorkflowHistory($loanApplication->id);

        $data_json = null;

        if ($loanApplication->current_status === "DRAFT") {
            // TODO: Get draft loan details
            $loanApplication_details = LoanApplicationDetail::where('loan_application_id', $loanApplication->id)->first();
            $data_json = $loanApplication_details->data_json;
        } else {
            // ── Flat fields ──────────────────────────────────────────────────────────
            $flatFields = LoanApplicationFieldResponses::where('loan_application_id', $loanApplication->id)
                ->whereNull('group_instance_id')
                ->where('is_active', true)
                ->get();

            // Resolve section_id for each field_key via Fields model
            $fieldKeys = $flatFields->pluck('field_key')->filter()->unique();
            $fieldDefs = Fields::whereIn('field_key', $fieldKeys)->get()->keyBy('field_key');

            // ── Group responses ──────────────────────────────────────────────────────
            $groupResponses = LoanApplicationGroupResponses::where('loan_application_id', $loanApplication->id)
                ->where('is_active', true)
                ->get();

            // Resolve section_id for each group_key via FieldGroups model
            $groupKeys = $groupResponses->pluck('group_key')->filter()->unique();
            $groupDefs = FieldGroups::whereIn('group_key', $groupKeys)->get()->keyBy('group_key');

            // ── Instances ────────────────────────────────────────────────────────────
            $groupResponseIds = $groupResponses->pluck('id');

            $allInstances = LoanApplicationGroupResponseInstances::whereIn('group_response_id', $groupResponseIds)
                ->where('is_active', true)
                ->get()
                ->groupBy('group_response_id');

            // ── Instance fields ──────────────────────────────────────────────────────
            $instanceIds = $allInstances->flatten()->pluck('id');

            $allInstanceFields = LoanApplicationFieldResponses::where('loan_application_id', $loanApplication->id)
                ->whereIn('group_instance_id', $instanceIds)
                ->where('is_active', true)
                ->get()
                ->groupBy('group_instance_id');

            // ── Resolve section keys ─────────────────────────────────────────────────
            $allSectionIds = collect($fieldDefs->pluck('section_id'))
                ->merge($groupDefs->pluck('section_id'))
                ->filter()
                ->unique();

            $sectionDefs = Sections::whereIn('id', $allSectionIds)
                ->get()
                ->keyBy('id');

            // ── Build sections map ───────────────────────────────────────────────────
            $sections = [];

            // Flat fields → their section
            foreach ($flatFields as $field) {
                $sectionId = $fieldDefs[$field->field_key]?->section_id;
                $sectionKey = $sectionDefs[$sectionId]?->section_key ?? null;

                if (!isset($sections[$sectionId])) {
                    $sections[$sectionId] = [
                        'id' => $sectionId,
                        'sectionKey' => $sectionKey,
                        'fields' => [],
                        'fieldGroups' => [],
                    ];
                }

                $sections[$sectionId]['fields'][$field->field_key] = json_decode($field->value_json, true);
            }

            // Groups → their section
            foreach ($groupResponses as $group) {
                $sectionId = $groupDefs[$group->group_key]?->section_id;
                $sectionKey = $sectionDefs[$sectionId]?->section_key ?? null;

                if (!isset($sections[$sectionId])) {
                    $sections[$sectionId] = [
                        'id' => $sectionId,
                        'sectionKey' => $sectionKey,
                        'fields' => [],
                        'fieldGroups' => [],
                    ];
                }

                $instances = ($allInstances[$group->id] ?? collect())
                    ->sortBy('instance_index')
                    ->map(function ($instance) use ($allInstanceFields) {
                        $instanceFields = [];

                        foreach (($allInstanceFields[$instance->id] ?? collect()) as $field) {
                            $instanceFields[$field->field_key] = json_decode($field->value_json, true);
                        }

                        // ── CHANGED: was [$instance->instance_key => $instanceFields]
                        // ── Now returns {instanceId, fields} shape to match input JSON
                        return [
                            'instanceId' => $instance->instance_key,
                            'fields' => $instanceFields,
                        ];
                    })
                    ->values()
                    ->toArray();

                $sections[$sectionId]['fieldGroups'][] = [
                    'groupKey' => $group->group_key,
                    'instances' => $instances,
                ];
            }

            // Preserve section order
            ksort($sections);
            $data_json = [
                'sections' => array_values($sections)
            ];
        }


//        $send_back_config = $workflow_log['send_back_config'] ?? null;
//        unset($workflow_log['send_back_config']);
        $send_back_config = null;
        if ($loanApplication->reverted == true) {
            $send_back_config = LoanApplicationWorkflowLog::query()
                ->where('loan_application_id', $loanApplication->id)
                ->where('revert_pending', true)
                ->orderByDesc('action_at')
                ->value('send_back_config');

            //$send_back_config = $raw ? json_decode($raw, true) : null;
        }

        return [
            'loan_application' => $loanApplication->toArray(),
            'data_json' => $data_json,
            'workflow_log' => $workflow_log,
            'send_back_config' => $send_back_config,
        ];
    }


    public function getAuthorizedStageIds(string $employee_id): array
    {
        $roleIds = DB::table('user_roles')
            ->where('employee_id', $employee_id)
            ->where('is_active', true)
            ->pluck('role_id')
            ->toArray();

        if (empty($roleIds)) {
            return [];
        }

        return DB::table('role_stages')
            ->whereIn('role_id', $roleIds)
            ->where('is_active', true)
            ->pluck('stage_id')
            ->unique()
            ->toArray();
    }


    private function resolveUiMeta(string $action_type, string $stage_status, ?string $toStageCode): array
    {
        if ($stage_status === 'APPROVED') {
            return ['Approved', 'green'];
        }

        switch (strtoupper($action_type)) {

            case 'DRAFT':
                return ['Draft', 'gray'];

            case 'SUBMIT':
                $label = $toStageCode
                    ? 'Pending ' . $this->humaniseStageCode($toStageCode) . ' Approval'
                    : 'Submitted';
                return [$label, 'blue'];

            case 'APPROVE':
                $label = $toStageCode
                    ? 'Pending ' . $this->humaniseStageCode($toStageCode) . ' Review'
                    : 'Approved';
                $color = $toStageCode ? 'blue' : 'green';
                return [$label, $color];

            case 'REVERT':
                return ['Sent Back for Correction', 'orange'];

            case 'REJECT':
                return ['Rejected', 'red'];

            default:
                return [$stage_status, 'gray'];
        }
    }


    private function humaniseStageCode(string $code): string
    {
        return ucwords(strtolower(str_replace('_', ' ', $code)));
    }


    public function getUnpickedHQLoansPastSLA(): Collection
    {
        $hqStages = WorkflowStage::where('stage_type', 'HO')
            ->get(['id', 'stage_code', 'stage_name', 'stage_type'])
            ->keyBy('id');

        if ($hqStages->isEmpty()) {
            return collect();
        }

        $loans = LoanApplication::whereIn('current_workflow_stage_id', $hqStages->keys())
            ->where('maker_status', 'SUBMITTED')
            ->where(function ($q) {
                $q->whereNull('assigned_to')
                    ->orWhere('assigned_to', '')
                    ->orWhere('assigned_to', 'null');
            })
            ->get();

        if ($loans->isEmpty()) {
            return collect();
        }

        $workflowDefinitions = WorkflowDefinition::whereIn(
            'id',
            $loans->pluck('workflow_definition_id')->unique()
        )->pluck('workflow_definition', 'id');

        $lastLogs = LoanApplicationWorkflowLog::whereIn('loan_application_id', $loans->pluck('id'))
            ->orderByDesc('action_at')
            ->orderByDesc('id')
            ->get()
            ->groupBy('loan_application_id')
            ->map(fn($logs) => $logs->first());

        return $loans->map(function (LoanApplication $loan) use ($hqStages, $workflowDefinitions, $lastLogs) {
            $lastLog = $lastLogs->get($loan->id);

            if (!$lastLog) {
                return null;
            }

            $workflowDefinition = collect($workflowDefinitions[$loan->workflow_definition_id] ?? [])
                ->map(fn($id) => (int)$id)
                ->values()
                ->all();

            if (empty($workflowDefinition)) {
                return null;
            }

            $actionAt = Carbon::parse($lastLog->action_at);
            $slaDeadline = $actionAt->copy()->addDay();

            // T+1 exceeded?
            if (now()->lte($slaDeadline)) {
                return null;
            }

            $fromId = (int)$lastLog->from_stage_id;
            $toId = (int)$lastLog->to_stage_id;

            // from and to must not be same
            if ($fromId === $toId) {
                return null;
            }

            $fromIndex = array_search($fromId, $workflowDefinition, true);
            $toIndex = array_search($toId, $workflowDefinition, true);

            // from stage must be less than to stage in workflow order
            if ($fromIndex === false || $toIndex === false || $fromIndex >= $toIndex) {
                return null;
            }

            $stage = $hqStages->get($loan->current_workflow_stage_id);

            return [
                'loan_id' => $loan->loan_id,
                'branch_code' => $loan->branch_code,
                'current_stage' => [
                    'id' => $stage->id,
                    'stage_code' => $stage->stage_code,
                    'stage_name' => $stage->stage_name,
                    'stage_type' => $stage->stage_type,
                ],
                'last_log' => [
                    'id' => $lastLog->id,
                    'from_stage_id' => $lastLog->from_stage_id,
                    'to_stage_id' => $lastLog->to_stage_id,
                    'action_type' => $lastLog->action_type,
                    'action_at' => $lastLog->action_at,
                ],
                'sla_deadline' => $slaDeadline->toDateTimeString(),
                'hours_overdue' => $slaDeadline->diffInHours(now()),
                'created_by' => is_array($loan->created_by)
                    ? $loan->created_by
                    : json_decode($loan->created_by, true),
            ];
        })->filter()->values();
    }

    public function createLoanOriginationId(string $productType): string
    {
        $prefix = $this->resolvePrefix($productType);
        $datePart = now()->format('Ymd');
        $randomPart = $this->generateRandomSuffix();

        return "{$prefix}-{$datePart}-{$randomPart}";
    }

    private function resolvePrefix(string $productType): string
    {
        return match ($productType) {
            AssetProductType::CARD->value   => 'CRD',
            AssetProductType::SME->value    => 'SME',
            AssetProductType::RETAIL->value => 'RTL',
            default => 'LON',
        };
    }

    private function generateRandomSuffix(int $length = 6): string
    {
        $alphabet = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
        $max = strlen($alphabet) - 1;

        $suffix = '';
        for ($i = 0; $i < $length; $i++) {
            $suffix .= $alphabet[random_int(0, $max)];
        }

        return $suffix;
    }
}
