<?php

namespace App\Helpers;

use App\Models\User;
use App\Models\Company;
use App\Models\InvoiceDetail;
use Illuminate\Support\Facades\DB;

class TableHelper
{
    public static function loopDataInQuotation($item)
    {
        $item->customer = DB::table('customers')->where('id', $item->customer_id)->first();
        $item->currency = DB::table('currencies')->where('id', $item->currency_id)->first();
        $item->user = DB::table('users')->where('id', $item->created_by)->first();
    }

    public static function formatDataInvoice($item)
    {
        $item->customer = DB::table('customers')->where('id', $item->customer_id)->first();
        $item->currency = DB::table('currencies')->where('id', $item->currency_id)->first();
        $item->quotation = DB::table('quotations')->where('id', $item->quotation_id)->first();
        $item->user = DB::table('users')->where('id', $item->created_by)->first();
    }

    public static function loopDataOfReceipt($item)
    {
        $item->customer = DB::table('customers')->where('id', $item->customer_id)->first();
        $item->currency = DB::table('currencies')->where('id', $item->currency_id)->first();
        $item->invoice = DB::table('invoices')->where('id', $item->invoice_id)->first();
        $item->user = DB::table('users')->where('id', $item->created_by)->first();
    }

    public static function loopDataInCompanyUser($item)
    {
        $item->company = DB::table('companies')->where('id', $item->company_id)->first();
        $item->user = DB::table('users')->where('id', $item->user_id)->first();
    }
}
