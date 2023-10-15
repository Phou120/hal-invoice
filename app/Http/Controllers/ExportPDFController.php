<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\InvoiceService;
use App\Services\QuotationService;
use Spatie\Browsershot\Browsershot;
use Illuminate\Support\Facades\File;

class ExportPDFController extends Controller
{
    /** exportPDF for invoice */
    public function exportPDFInvoice(Request $request)
    {
        $invoice = resolve(InvoiceService::class)->listInvoice($request);

        $view = view('invoices.invoice')
        ->with('data', $invoice)
        ->render();

        $file_name = 'invoice' . '.pdf';
        $file_url = public_path('images/invoice/pdf/' . $file_name);
        if (!File::isDirectory(public_path('images/invoice/pdf/'))) {
            File::makeDirectory(public_path('images/invoice/pdf/'), 0777, true, true);

        }

        $footerHtml ='<br><br>
                <p style="font-size: 10px;color: #999; margin: 15px 40px; clear:both; position: relative; top: 20px;text-align:right;display: block;
                margin-block-end: 1em;
                margin-inline-end: 0px;">
                <span class="pageNumber"></span>/<span class="totalPages"></span>
                </p>';

        Browsershot::html($view)
        ->userAgent('Mozilla/5.0 (Linux; Android 9; Redmi Note 8 Pro) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.99 Mobile Safari/537.36')
        ->windowSize(250, 450)
        ->deviceScaleFactor(3)
        ->touch()
        ->mobile()
        ->landscape(false)
        ->fullPage()
        ->showBrowserHeaderAndFooter(true)
        ->footerHtml($footerHtml)
        ->hideHeader()
        ->disableJavascript()
        ->format('A4')
        ->margins(6, 0, 8, 0)
        ->timeout(60)
        ->save($file_url);

        return response()->json(['error' => false, 'message' => 'success'], 200);

    }

    /** exportPDF for quotation */
    public function exportPDFQuotation(Request $request)
    {
        $quotation = resolve(QuotationService::class)->listQuotation($request);

        $view = view('quotations.quotation')
        ->with('data', $quotation)
        ->render();
        // return $view;

        $file_name = 'quotation' . '.pdf';
        $file_url = public_path('images/quotation/pdf/' . $file_name);
        if (!File::isDirectory(public_path('images/quotation/pdf/'))) {
            File::makeDirectory(public_path('images/quotation/pdf/'), 0777, true, true);

        }

        $footerHtml ='<br><br>
                <p style="font-size: 10px;color: #999; margin: 15px 40px; clear:both; position: relative; top: 20px;text-align:right;display: block;
                margin-block-end: 1em;
                margin-inline-end: 0px;">
                <span class="pageNumber"></span>/<span class="totalPages"></span>
                </p>';

        Browsershot::html($view)
        ->userAgent('Mozilla/5.0 (Linux; Android 9; Redmi Note 8 Pro) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.99 Mobile Safari/537.36')
        ->windowSize(250, 450)
        ->deviceScaleFactor(3)
        ->touch()
        ->mobile()
        ->landscape(false)
        ->fullPage()
        ->showBrowserHeaderAndFooter(true)
        ->footerHtml($footerHtml)
        ->hideHeader()
        ->disableJavascript()
        ->format('A4')
        ->margins(6, 0, 8, 0)
        ->timeout(60)
        ->save($file_url);

        return response()->json(['error' => false, 'message' => 'success'], 200);
    }
}
