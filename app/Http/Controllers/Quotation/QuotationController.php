<?php

namespace App\Http\Controllers\Quotation;

use Illuminate\Http\Request;
use App\Services\QuotationService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Quotation\QuotationRequest;

class QuotationController extends Controller
{
    public $quotationService;

    public function __construct(QuotationService $quotationService)
    {
        $this->quotationService = $quotationService;
    }


    public function addQuotation(QuotationRequest $request)
    {
        return $this->quotationService->addQuotation($request);
    }

    public function listQuotations(Request $request)
    {
        return $this->quotationService->listQuotations($request);
    }

    public function listQuotationDetail(QuotationRequest $request)
    {
        return $this->quotationService->listQuotationDetail($request);
    }

    public function addQuotationDetail(QuotationRequest $request)
    {
        return $this->quotationService->addQuotationDetail($request);
    }

    public function editQuotation(QuotationRequest $request)
    {
        return $this->quotationService->editQuotation($request);
    }

    public function editQuotationDetail(QuotationRequest $request)
    {
        return $this->quotationService->editQuotationDetail($request);
    }

    public function deleteQuotationDetail(QuotationRequest $request)
    {
        return $this->quotationService->deleteQuotationDetail($request);
    }

    public function deleteQuotation(QuotationRequest $request)
    {
        return $this->quotationService->deleteQuotation($request);
    }

    public function updateQuotationStatus(QuotationRequest $request)
    {
        return $this->quotationService->updateQuotationStatus($request);
    }

    public function updateDetailStatus(QuotationRequest $request)
    {
        return $this->quotationService->updateDetailStatus($request);
    }
}
