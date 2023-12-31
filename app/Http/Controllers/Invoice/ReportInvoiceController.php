<?php

namespace App\Http\Controllers\Invoice;

use Illuminate\Http\Request;
use App\Services\ReportService;
use App\Http\Controllers\Controller;
use App\Services\ReportInvoiceService;

class ReportInvoiceController extends Controller
{
    public $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function reportInvoice(Request $request)
    {
        return $this->reportService->reportInvoice($request);
    }
}
