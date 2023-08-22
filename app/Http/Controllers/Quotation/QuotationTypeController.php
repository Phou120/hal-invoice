<?php

namespace App\Http\Controllers\Quotation;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\quotationType\QuotationTypeService;
use App\Http\Requests\Quotation\QuotationTypeRequest;

class QuotationTypeController extends Controller
{
    public $quotationTypeService;

    public function __construct(QuotationTypeService $quotationTypeService)
    {
        $this->quotationTypeService = $quotationTypeService;
    }

    public function createQuotationType(QuotationTypeRequest $request)
    {
        return $this->quotationTypeService->createQuotationType($request);
    }

    public function listQuotationTypes(Request $request)
    {
        return $this->quotationTypeService->listQuotationTypes($request);
    }

    public function updateQuotationType(QuotationTypeRequest $request)
    {
        return $this->quotationTypeService->updateQuotationType($request);
    }

    public function deleteQuotationType(QuotationTypeRequest $request)
    {
        return $this->quotationTypeService->deleteQuotationType($request);
    }
}
