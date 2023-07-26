<?php

namespace App\Http\Controllers\CompanyUser;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\CompanyUser\CompanyUserService;
use App\Http\Requests\ComapnyUser\CompanyUserRequest;

class CompanyUserController extends Controller
{
    public $companyUserService;

    public function __construct(CompanyUserService $companyUserService)
    {
        $this->companyUserService = $companyUserService;
    }


    public function createCompanyUser(CompanyUserRequest $request)
    {
        return $this->companyUserService->createCompanyUser($request);
    }

    public function listCompanyUser()
    {
        return $this->companyUserService->listCompanyUser();
    }

    public function updateCompanyUser(CompanyUserRequest $request)
    {
        return $this->companyUserService->updateCompanyUser($request);
    }

    public function deleteCompanyUser(CompanyUserRequest $request)
    {
        return $this->companyUserService->deleteCompanyUser($request);
    }
}
