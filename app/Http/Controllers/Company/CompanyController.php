<?php

namespace App\Http\Controllers\Company;

use Illuminate\Http\Request;
use App\Services\CompanyService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Company\CompanyRequest;

class CompanyController extends Controller
{
    public $companyService;

    public function __construct(CompanyService $companyService)
    {
        $this->companyService = $companyService;
    }


    public function addCompany(CompanyRequest $request)
    {
        return $this->companyService->addCompany($request);
    }

    public function listCompanies(Request $request)
    {
        return $this->companyService->listCompanies($request);
    }

    public function editCompany(CompanyRequest $request)
    {
        return $this->companyService->editCompany($request);
    }

    public function deleteCompany(CompanyRequest $request)
    {
        return $this->companyService->deleteCompany($request);
    }
}
