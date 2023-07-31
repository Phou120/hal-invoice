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
        if($request['quotation_id']){
            return $this->invoiceService->addInvoice($request);
        } else {
            return $this->invoiceNoQuotationService->addInvoice($request);
        }
    }

    public function listInvoices()
    {
        return $this->invoiceService->listInvoices();
    }

    public function listInvoiceDetail(InvoiceRequest $request)
    {
        return $this->invoiceService->listInvoiceDetail($request);
    }

    public function addInvoiceDetail(InvoiceRequest $request)
    {
        $invoice = Invoice::where('id', $request['id'])->first();
        if($invoice->quotation_id){
            return $this->invoiceService->addInvoiceDetail($request);
        } else {
            return $this->invoiceNoQuotationService->addInvoiceDetail($request);
        }
    }

    public function editInvoice(InvoiceRequest $request)
    {
        $invoice = Invoice::where('id', $request['id'])->first();
        if($invoice['quotation_id']){
            return $this->invoiceService->editInvoice($request);
        } else {
            return $this->invoiceNoQuotationService->editInvoice($request);
        }
    }

    public function editInvoiceDetail(InvoiceRequest $request)
    {
        $detail = InvoiceDetail::where('id', $request['id'])->first();
        $invoice = Invoice::where('id', $detail['invoice_id'])->first();
        if($invoice['quotation_id']){
            return $this->invoiceService->editInvoiceDetail($request);
        } else {
            return $this->invoiceNoQuotationService->editInvoiceDetailNoQuotationID($request);
        }
    }

    public function deleteInvoiceDetail(InvoiceRequest $request)
    {
        $detail = InvoiceDetail::where('id', $request['id'])->first();
        $invoice = Invoice::where('id', $detail['invoice_id'])->first();
        if($invoice['quotation_id']){
            return $this->invoiceService->deleteInvoiceDetail($request);
        } else {
            return $this->invoiceNoQuotationService->deleteInvoiceDetailNoQuotationID($request);
        }
    }

    public function deleteInvoice(InvoiceRequest $request)
    {
        $invoice = Invoice::where('id', $request['id'])->first();
        if($invoice['quotation_id']){
            return $this->invoiceService->deleteInvoice($request);
        } else {
            return $this->invoiceNoQuotationService->deleteInvoice($request);
        }
    }

    public function updateInvoiceStatus(InvoiceRequest $request)
    {
        $invoice = Invoice::where('id', $request['id'])->first();
        if($invoice['quotation_id']){
            return $this->invoiceService->updateInvoiceStatus($request);
        } else {
            return $this->invoiceNoQuotationService->updateInvoiceStatus($request);
        }
    }
}
