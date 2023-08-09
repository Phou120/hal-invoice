<?php

namespace App\Helpers;
class myHelper
{
    const TAX = 7;
    const INVOICE_STATUS = [
        'CREATED' => 'created',
        'APPROVED' => 'approved',
        'INPROGRESS' => 'inprogress',
        'COMPLETED' => 'completed',
        'CANCELLED' => 'cancelled'
    ];

    /** filter of quotation */
    public static function quotationFilter($query, $request)
    {
        if ($request->status !== null) {
            $query->where('status', $request->status);
        }

        if ($request->start_date && $request->end_date) {
            $query->whereRaw("DATE(quotations.start_date) BETWEEN ? AND ?", [$request->start_date, $request->end_date]);
        }

        return $query;
    }

    /** filter of invoice */
    public static function invoiceFilter($query, $request)
    {
        if ($request->status !== null) {
            $query->where('status', $request->status);
        }

        if ($request->start_date && $request->end_date) {
            $query->whereRaw("DATE(invoices.start_date) BETWEEN ? AND ?", [$request->start_date, $request->end_date]);
        }

        return $query;
    }
}
