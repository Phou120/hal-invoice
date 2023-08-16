<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Receipt;
use App\Models\Quotation;
use App\Traits\ResponseAPI;
use App\Helpers\TableHelper;
use App\Helpers\filterHelper;
use Illuminate\Support\Facades\DB;
use App\Services\returnData\ReturnService;

class ReportService
{
    use ResponseAPI;

    public function reportInvoice($request)
    {
        $invoiceQuery = Invoice::query();

        // filters date
        $invoiceQuery = FilterHelper::filterDate($invoiceQuery, $request);

        // Define invoice statuses
        $statuses = (new ReturnService())->status($invoiceQuery);

        // Initialize data array
        $responseData = (new ReturnService())->responseData($invoiceQuery);

        /** foreach data */
        $foreach = (new ReturnService())->foreachData($statuses, $invoiceQuery, $responseData);

        return response()->json($foreach, 200);
    }

    public function reportQuotation($request)
    {
        $quotationQuery = Quotation::query();

        // filters date
        $quotationQuery = FilterHelper::quotationFilter($quotationQuery, $request);

        $totalBill = $quotationQuery->count(); // count all Quotations

        $quotation = $quotationQuery->orderBy('quotations.id', 'asc')->get();

        $totalPrice = $quotation->sum('total'); // sum all Quotations

        $statuses = (new ReturnService())->statusQuotation($quotationQuery); // status for Quotations

        $responseData = [];

        /** foreach */
        $foreach = (new ReturnService())->foreach($statuses, $quotationQuery, $responseData);

        $countCompany = TableHelper::countCompany($quotationQuery);
        $countUser = TableHelper::countUser($quotationQuery);

        $response = (new ReturnService())->response(
            $countCompany, $countUser, $totalBill, $totalPrice,
            $foreach['quotationStatusCreated']['count'],
            $foreach['quotationStatusCreated']['total'],
            $foreach['quotationStatusApproved']['count'],
            $foreach['quotationStatusApproved']['total'],
            $foreach['quotationStatusCompleted']['count'],
            $foreach['quotationStatusCompleted']['total'],
            $foreach['quotationStatusCancelled']['count'],
            $foreach['quotationStatusCancelled']['total'],
            $foreach['quotationStatusInprogress']['count'],
            $foreach['quotationStatusInprogress']['total'],
        );

        return response()->json($response, 200);
    }


    public function reportReceipt($request)
    {
        $query = Receipt::query();

        $totalReceipt = (clone $query)->count(); // count all invoices

        $receipt = (clone $query)->orderBy('receipts.id', 'asc')->get();

        $receipt = filterHelper::getReceipt($receipt); // Apply transformation

        $totalPrice = $receipt->sum('total');

        return [
            'totalReceipt' => $totalReceipt,
            'totalPrice' => $totalPrice,
            'query' => $receipt
        ];
    }

    public function reportCompanyCustomer($request)
    {
        $companyCount = DB::table('companies')->count();
        $customerCount = DB::table('customers')->count();

        return [
            'companyCount' => $companyCount,
            'customerCount' => $customerCount,
        ];
    }
}
