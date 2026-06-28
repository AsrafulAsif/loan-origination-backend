<?php

namespace App\Http\Controllers\AccountDetails;

use App\Services\AccountDetails\GetAccountDetailsFromCbsService;

class AccountDetailsController
{
    protected GetAccountDetailsFromCbsService $getAccountDetailsFromCbsService;
    public function __construct(GetAccountDetailsFromCbsService $getAccountDetailsFromCbsService)
    {
        $this->getAccountDetailsFromCbsService = $getAccountDetailsFromCbsService;
    }

    public function getAccountInfo(string $bank_account_number):array
    {
        return $this->getAccountDetailsFromCbsService->getAccountInfo($bank_account_number);
    }
}
