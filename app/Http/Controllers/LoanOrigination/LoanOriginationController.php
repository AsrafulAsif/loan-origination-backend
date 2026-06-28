<?php

namespace App\Http\Controllers\LoanOrigination;

use App\Http\Requests\LoanOrigination\LoanAssignRequest;
use App\Http\Requests\LoanOrigination\LoanCreateRequest;
use App\Http\Requests\LoanOrigination\LoanPickRequest;
use App\Http\Requests\LoanOrigination\LoanReviewRequest;
use App\Http\Requests\LoanOrigination\LoanSearchRequest;
use App\Services\LoanOrigination\LoanOriginationService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Throwable;

class LoanOriginationController
{
    use ApiResponseTrait;

    protected LoanOriginationService $loanOriginationService;
    public function __construct(LoanOriginationService $loanOriginationService)
    {
        $this->loanOriginationService = $loanOriginationService;
    }


    /**
     * @throws Throwable
     */
    public function createLoanDraft(LoanCreateRequest $request) : JsonResponse
    {
        $requestData = $request->validated();
        $loanId = $this->loanOriginationService->createLoanForDraft($requestData);
        return $this->successResponse(['loan_id' => $loanId], 'Loan draft saved successfully', 201);
    }


    /**
     * @throws Throwable
     */
    public function createLoanSubmit(LoanCreateRequest $request) : JsonResponse
    {
        $requestData = $request->validated();
        $loanId = $this->loanOriginationService->createLoanForSubmit($requestData);
        return $this->successResponse(['loan_id' => $loanId], 'Loan successfully created', 201);
    }

    /**
     * @throws Throwable
     */
    public function loanReview(LoanReviewRequest $request) : JsonResponse
    {
        $requestData = $request->validated();
        $this->loanOriginationService->reviewLoan($requestData);
        return $this->successResponse(null,'Loan review saved successfully');

    }

    /**
     * @throws Throwable
     */
    public function pickLoan(LoanPickRequest $request): JsonResponse
    {
        $this->loanOriginationService->pickLoan($request->validated());
        return $this->successResponse(null, 'Loan picked successfully');
    }

    /**
     * @throws Throwable
     */
    public function assignLoan(LoanAssignRequest $request): JsonResponse
    {
        $this->loanOriginationService->assignLoan($request->validated());
        return $this->successResponse(null, 'Loan assigned successfully');
    }


    public function getDashboardLoans(LoanSearchRequest $request): JsonResponse
    {
        $responseData = $this->loanOriginationService->getDashboardLoans($request->validated());
        return $this->paginatedResponse($responseData);
    }

    public function getAllLoansCreatedByMe(LoanSearchRequest $request): JsonResponse
    {
        $responseData = $this->loanOriginationService->getAllLoansCreatedByMe($request->validated());
        return $this->paginatedResponse($responseData);
    }

    public function getLoanById(string $loan_id): JsonResponse
    {
        $responseData =$this->loanOriginationService->getLoanById($loan_id);
        return $this->successResponse($responseData);
    }

    public function getTPlusOneDayLoans()
    {
        return $this->successResponse($this->loanOriginationService->getUnpickedHQLoansPastSLA());
    }


}
