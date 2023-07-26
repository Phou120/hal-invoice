<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
class TableHelper
{
    public static function format($item)
    {
        $item->customer = DB::table('customers')->where('id', $item->customer_id)->first();
        $item->currency = DB::table('currencies')->where('id', $item->currency_id)->first();
        $item->quotation = DB::table('quotations')->where('id', $item->currency_id)->first();
        $item->user = DB::table('users')->where('id', $item->created_by)->first();
        //->where('status', myHelper::INVOICE_STATUS['CREATED'])
    }

    public static function loopDataInCompanyUser($item)
    {
        $item->company = DB::table('companies')->where('id', $item->company_id)->first();
        $item->user = DB::table('users')->where('id', $item->user_id)->first();
    }
}
