<?php

namespace App\Services;

use App\Models\Invoice;
use App\Helpers\myHelper;
use App\Traits\ResponseAPI;
use Illuminate\Support\Facades\DB;

class ReportInvoiceService
{
    use ResponseAPI;

    public function reportInvoice($request)
    {
        $invoiceQuery = DB::table('invoices');
        // $invoiceQuery = Invoice::select('invoices.*');

        $invoiceQuery = myHelper::reportInvoice($invoiceQuery, $request);

        $invoices = (clone $invoiceQuery)->where('status', $request->status)->first();

        return response()->json([
        'listInvoices' => $invoices
        ], 200);
    }
}
