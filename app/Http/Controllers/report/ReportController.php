<?php

namespace App\Http\Controllers\report;

use Illuminate\Http\Request;
use App\Services\ReportService;
use App\Http\Controllers\Controller;

class ReportController extends Controller
{
    public $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }


    public function reportQuotation(Request $request)
    {
        return $this->reportService->reportQuotation($request);
    }


    public function reportReceipt(Request $request)
    {
        return $this->reportService->reportReceipt($request);
    }

}
