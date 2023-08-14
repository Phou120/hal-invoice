<?php

namespace App\Helpers;

use App\Models\InvoiceDetail;
use App\Models\QuotationDetail;
use App\Models\ReceiptDetail;
use App\Services\CalculateService;
use Illuminate\Support\Facades\DB;

class filterHelper
{
    const TAX = 7;
    const INVOICE_STATUS = [
        'CREATED' => 'created',
        'APPROVED' => 'approved',
        'INPROGRESS' => 'inprogress',
        'COMPLETED' => 'completed',
        'CANCELLED' => 'canceled'
    ];


    /** filter of quotation */
    public static function quotationFilterStatus($query, $request)
    {
        if ($request->status !== null) {
            $query->where('status', $request->status);
        }

        return $query;
    }

    public static function quotationFilter($query, $request)
    {
        if ($request->start_date && $request->end_date) {
            $query->whereRaw("DATE(quotations.start_date) BETWEEN ? AND ?", [$request->start_date, $request->end_date]);
        }

        return $query;
    }

    /** get data of invoiceDetail */
    public static function getInvoicesStatus($invoiceStatus)
    {
        $invoiceStatus->transform(function($item) {
            $invoiceDetail = InvoiceDetail::where('invoice_id', $item->id)
                ->select(DB::raw("IFNULL(sum(total), 0) as total"))
                ->first()->total;

            $tax = $item->tax;
            $discount = $item->discount;

            $sumTotal = (new CalculateService())->calculateTotalInvoice($invoiceDetail, $tax, $discount);

            // Update the item with the calculated total
            $item->total = $sumTotal;

            return $item;
        });

        return $invoiceStatus;
    }

    public static function getReceipt($listReceipt)
    {
        $listReceipt->transform(function($item) {
            $receiptDetail = ReceiptDetail::where('receipt_id', $item->id)
                ->select(DB::raw("IFNULL(sum(total), 0) as total"))
                ->first()->total;

            $tax = $item->tax;
            $discount = $item->discount;

            $sumTotal = (new CalculateService())->calculateTotalInvoice($receiptDetail, $tax, $discount);

            // Update the item with the calculated total
            $item->total = $sumTotal;

            return $item;
        });

        return $listReceipt;
    }

    public static function getQuotationStatus($invoiceStatus)
    {
        $invoiceStatus->transform(function($item) {
            $invoiceDetail = InvoiceDetail::where('invoice_id', $item['id'])
                ->select(DB::raw("IFNULL(sum(total), 0) as total"))->first()->total;

            $tax = $item['tax'];
            $discount = $item['discount'];

            $sumTotal = (new CalculateService())->calculateTotalInvoice($invoiceDetail, $tax, $discount);

            // Update the item with the calculated total
            $item['total'] = $sumTotal;

            return $item;
        });

        return $invoiceStatus;
    }

    /** map data in invoice */
    public static function mapDataInvoice($listInvoice)
    {
        $listInvoice->transform(function($item) {
            $invoiceDetail = InvoiceDetail::where('invoice_id', $item['id'])
                ->select(DB::raw("IFNULL(sum(total), 0) as total"))->first()->total;

            $tax = $item['tax'];
            $discount = $item['discount'];

            $sumTotal = (new CalculateService())->calculateTotalInvoice($invoiceDetail, $tax, $discount);

            // Update the item with the calculated total
            $item['total'] = $sumTotal;

             /** loop data */
            TableHelper::formatDataInvoice($item);

            return $item;
        });

        return $listInvoice;
    }


    public static function mapDataReceipt($listReceipt)
    {
        $listReceipt->transform(function($item) {
            $receiptDetail = ReceiptDetail::where('receipt_id', $item->id)
                ->select(DB::raw("IFNULL(sum(total), 0) as total"))
                ->first()->total;

            $tax = $item->tax;
            $discount = $item->discount;

            $sumTotal = (new CalculateService())->calculateTotalInvoice($receiptDetail, $tax, $discount);

            // Update the item with the calculated total
            $item->total = $sumTotal;

            TableHelper::loopDataOfReceipt($item);

            return $item;
        });

        return $listReceipt;
    }


    public static function invoiceFilter($query, $request)
    {
        if ($request->start_date && $request->end_date) {
            $query->whereRaw("DATE(invoices.start_date) BETWEEN ? AND ?", [$request->start_date, $request->end_date]);
        }

        return $query;
    }

    public static function invoiceFilterStatus($query, $request)
    {
        if ($request->status !== null) {
            $query->where('status', $request->status);
        }

        return $query;
    }

    public static function receiptFilter($query, $request)
    {
        if ($request->receipt_date) {
            $query->whereRaw("DATE(receipts.receipt_date) = ?", [$request->receipt_date]);
        }

        return $query;
    }

    public static function reportInvoice($invoiceQuery, $request)
    {
        if ($request->status == 'created') {
            $invoiceQuery->addSelect(DB::raw('(SELECT COUNT(*) FROM invoices WHERE status = "created") as count_created'));

        } elseif ($request->status == 'approved'){
            $invoiceQuery->addSelect(DB::raw('(SELECT COUNT(*) FROM invoices WHERE status = "approved") as count_approved'));

        } elseif ($request->status == ''){

        }

        return $invoiceQuery;

    }
}
