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

        $invoiceQuery = filterHelper::reportInvoice($invoiceQuery, $request);

        $invoices = (clone $invoiceQuery)->where('status', $request->status)->first();

        return response()->json([
        'listInvoices' => $invoices
        ], 200);
    }
}
