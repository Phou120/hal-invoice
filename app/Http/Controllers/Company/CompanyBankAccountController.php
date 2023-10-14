<?php

namespace App\Http\Controllers\Company;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\CompanyUser\CompanyBankAccountService;
use App\Http\Requests\ComapnyUser\CompanyBankAccountRequest;

class CompanyBankAccountController extends Controller
{
    public $companyBankAccountService;

    public function __construct(CompanyBankAccountService $companyBankAccountService)
    {
        $this->companyBankAccountService = $companyBankAccountService;
    }

    public function createCompanyBankAccount(CompanyBankAccountRequest $request)
    {
        return $this->companyBankAccountService->createCompanyBankAccount($request);
    }

    public function listCompanyBankAccounts(Request $request)
    {
        return $this->companyBankAccountService->listCompanyBankAccounts($request);
    }

    public function updateCompanyBankAccount(CompanyBankAccountRequest $request)
    {
        return $this->companyBankAccountService->updateCompanyBankAccount($request);
    }

    public function deleteCompanyBankAccount(CompanyBankAccountRequest $request)
    {
        return $this->companyBankAccountService->deleteCompanyBankAccount($request);
    }

    public function updateStatusBankAccount(CompanyBankAccountRequest $request)
    {
        return $this->companyBankAccountService->updateStatusBankAccount($request);
    }
}
