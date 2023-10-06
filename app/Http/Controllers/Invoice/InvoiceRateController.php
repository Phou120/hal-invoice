<?php

namespace App\Http\Controllers\Invoice;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\invoiceRate\InvoiceRateService;

class InvoiceRateController extends Controller
{
    public $invoiceRateService;

    public function __construct(InvoiceRateService $invoiceRateService)
    {
        $this->invoiceRateService = $invoiceRateService;
    }

    public function invoiceRates(Request $request)
    {
        return $this->invoiceRateService->invoiceRates($request);
    }
}
