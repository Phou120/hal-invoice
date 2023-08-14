<?php

namespace App\Services;

use App\Traits\ResponseAPI;
use App\Helpers\filterHelper;
use Illuminate\Support\Facades\DB;

class ReportInvoiceService
{
    use ResponseAPI;

    public function reportInvoice($request)
    {
        $invoiceQuery = DB::table('invoices');
        // $invoiceQuery = Invoice::select('invoices.*');

        //$invoiceQuery = filterHelper::reportInvoice($invoiceQuery, $request);

        $totalBill = (clone $invoiceQuery)->count(); // count all invoices
        //$invoices = (clone $invoiceQuery)->where('status', $request->status)->first();

        // return response()->json([
        // 'listInvoices' => $totalBill
        // ], 200);
        $invoice = (clone $invoiceQuery)->orderBy('invoices.id', 'asc')->get();

        $invoice = filterHelper::getInvoicesStatus($invoice);

        $totalPrice = $invoice->sum('total'); // sum total of invoices all

        return [$totalBill, $totalPrice];
    }
}
