<?php

namespace App\Http\Controllers\Invoice;

use Illuminate\Http\Request;
use App\Services\InvoiceService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Invoice\InvoiceRequest;

class InvoiceController extends Controller
{
    public $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }


    public function addInvoice(InvoiceRequest $request)
    {
        return $this->invoiceService->addInvoice($request);
    }

    public function listInvoices()
    {
        return $this->invoiceService->listInvoices();
    }

    public function listInvoiceDetail($id)
    {
        return $this->invoiceService->listInvoiceDetail($id);
    }

    public function addInvoiceDetail(InvoiceRequest $request)
    {
        return $this->invoiceService->addInvoiceDetail($request);
    }

    public function editInvoice(InvoiceRequest $request)
    {
        return $this->invoiceService->editInvoice($request);
    }

    public function editInvoiceDetail(InvoiceRequest $request)
    {
        return $this->invoiceService->editInvoiceDetail($request);
    }

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
