<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;
use App\Services\InvoiceService;
use Spatie\Browsershot\Browsershot;
use Illuminate\Support\Facades\File;


class ExportPDFController extends Controller
{

    public function exportPDF()
    {
        $invoice = resolve(InvoiceService::class)->listInvoiceDetail(1)->getData();
        $view = view('invoices.invoice')
        ->with('invoice', $invoice)
        ->render();
        return $view;
        $file_name = 'invoice' . '.pdf';
        $file_url = public_path('images/invoice/pdf/' . $file_name);
        if (!File::isDirectory(public_path('images/invoice/pdf/'))) {
            File::makeDirectory(public_path('images/invoice/pdf/'), 0777, true, true);
        }

        Browsershot::html($view)
        ->noSandbox()
        ->format('A4')
        ->landscape(false)
        ->margins(0, 1, 0, 1)
        ->save($file_url);
    }
}
