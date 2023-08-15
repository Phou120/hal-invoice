<?php

namespace App\Services;

use App\Traits\ResponseAPI;
use App\Models\InvoiceDetail;
use Illuminate\Support\Facades\DB;


class CalculateService
{
    use ResponseAPI;

    public function getInvoices($query, $calculateService)
    {
        $invoices = (clone $query)->orderBy('invoices.id', 'asc')->get();

        $invoices->transform(function($item) use ($calculateService) {
            $invoiceDetail = InvoiceDetail::where('invoice_id', $item['id'])
                ->select(DB::raw("IFNULL(sum(total), 0) as total"))->first()->total;

            $tax = $item['tax'];
            $discount = $item['discount'];

            $sumTotal = $calculateService->calculateTotalInvoice($invoiceDetail, $tax, $discount);

            $item['total'] = $sumTotal;

            return $item;
        });

        return $invoices;
    }
}
