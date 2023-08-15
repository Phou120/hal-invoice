<?php

namespace App\Http\Controllers\Invoice;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\ReportInvoiceService;

class ReportInvoiceController extends Controller
{
    public $reportInvoiceService;

    public function __construct(ReportInvoiceService $reportInvoiceService)
    {
        $this->reportInvoiceService = $reportInvoiceService;
    }

    public function reportInvoice(Request $request)
    {
        return $this->reportInvoiceService->reportInvoice($request);
    }
}
