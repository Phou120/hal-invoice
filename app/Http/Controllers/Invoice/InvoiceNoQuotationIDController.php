<?php

namespace App\Http\Controllers\Invoice;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\InvoiceNoQuotationService;
use App\Services\InvoiceNoQuotationIDService;
use App\Http\Requests\Invoice\InvoiceNoQuotationRequest;

class InvoiceNoQuotationIDController extends Controller
{
    public $invoiceNoQuotationService;

    public function __construct(InvoiceNoQuotationService $invoiceNoQuotationService)
    {
        $this->invoiceNoQuotationService = $invoiceNoQuotationService;
    }

    public function addInvoiceNoQuotationID(InvoiceNoQuotationRequest $request)
    {
        return $this->invoiceNoQuotationService->addInvoiceNoQuotationID($request);
    }

    public function editInvoiceNoQuotationID(InvoiceNoQuotationRequest $request)
    {
        return $this->invoiceNoQuotationService->editInvoiceNoQuotationID($request);
    }

    public function addInvoiceDetailNoQuotationID(InvoiceNoQuotationRequest $request)
    {
        return $this->invoiceNoQuotationService->addInvoiceDetailNoQuotationID($request);
    }

    public function editInvoiceDetailNoQuotationID(InvoiceNoQuotationRequest $request)
    {
        return $this->invoiceNoQuotationService->editInvoiceDetailNoQuotationID($request);
    }
}
