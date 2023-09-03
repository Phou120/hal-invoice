<?php

namespace App\Http\Controllers\Quotation;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\quotationType\QuotationRateService;

class QuotationRateController extends Controller
{
    public $quotationRateService;

    public function __construct(QuotationRateService $quotationRateService)
    {
        $this->quotationRateService = $quotationRateService;
    }

    public function listQuotationRates(Request $request)
    {
        return $this->quotationRateService->listQuotationRates($request);
    }
}
