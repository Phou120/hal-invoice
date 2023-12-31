<?php

namespace App\Http\Controllers\Invoice;

use App\Models\Invoice;
use Illuminate\Http\Request;
use App\Models\InvoiceDetail;
use App\Services\InvoiceService;
use App\Http\Controllers\Controller;
use App\Services\InvoiceNoQuotationService;
use App\Http\Requests\Invoice\InvoiceRequest;

class InvoiceController extends Controller
{
    public $invoiceService;
    public $invoiceNoQuotationService;

    public function __construct(
        InvoiceService  $invoiceService,
        InvoiceNoQuotationService $invoiceNoQuotationService
    )
    {
        $this->invoiceService = $invoiceService;
        $this->invoiceNoQuotationService = $invoiceNoQuotationService;
    }


    public function addInvoice(InvoiceRequest $request)
    {
        return $this->invoiceService->addInvoice($request);
    }

    public function listInvoices(Request $request)
    {
        return $this->invoiceService->listInvoices($request);
    }

    public function listInvoiceDetail(InvoiceRequest $request)
    {
        return $this->invoiceService->listInvoiceDetail($request);
    }

    public function addInvoiceDetail(InvoiceRequest $request)
    {
        return $this->invoiceService->addInvoiceDetail($request);
    }

    public function editInvoice(InvoiceRequest $request)
    {
        return $this->invoiceService->editInvoice($request);
    }

    // public function editInvoiceDetail(InvoiceRequest $request)
    // {
    //     return $this->invoiceService->editInvoiceDetail($request);
    // }

    public function deleteInvoiceDetail(InvoiceRequest $request)
    {
        return $this->invoiceService->deleteInvoiceDetail($request);
    }

    public function deleteInvoice(InvoiceRequest $request)
    {
        return $this->invoiceService->deleteInvoice($request);
    }

    public function updateInvoiceStatus(InvoiceRequest $request)
    {
        return $this->invoiceService->updateInvoiceStatus($request);
    }
}
