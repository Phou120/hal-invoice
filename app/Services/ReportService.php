<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\Receipt;
use App\Models\Customer;
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

        //$countUser = $invoiceQuery->select(DB::raw('(SELECT COUNT(id) FROM users WHERE users.id) as user_count'))->first();

        /** foreach data */
        $foreach = (new ReturnService())->foreachData($statuses, $invoiceQuery, $responseData);

        /** count user and company */
        $countUserCompany = (new ReturnService())->countUserCompany($invoiceQuery);

        /** output data  */
        $outputData = (new ReturnService())->outputData($foreach, $countUserCompany);

        return response()->json($outputData, 200);
    }

    public function reportQuotation($request)
    {
        $quotationQuery = Quotation::query();

        // filters date
        $quotationQuery = FilterHelper::quotationFilter($quotationQuery, $request);

        $totalBill = $quotationQuery->count(); // count all Quotations

        //$quotation = $quotationQuery->orderBy('quotations.id', 'asc')->get();

        $totalPrice = $quotationQuery->sum('total'); // sum all Quotations

        $statuses = [
            'CREATED' => 'quotationStatusCreated',
            'APPROVED' => 'quotationStatusApproved',
            'INPROGRESS' => 'quotationStatusInprogress',
            'COMPLETED' => 'quotationStatusCompleted',
            'CANCELLED' => 'quotationStatusCancelled',
        ];

        $responseData = [];

        /** foreach  */
        $foreach = (new ReturnService())->foreach($statuses, $quotationQuery, $responseData);

        /** count user and company */
        $countUserCompany = (new ReturnService())->countUserCompany($quotationQuery);

        $response = (new ReturnService())->response(
            $totalBill, $totalPrice,
            $foreach['quotationStatusCreated']['count'],
            $foreach['quotationStatusCreated']['total'],
            $foreach['quotationStatusApproved']['count'],
            $foreach['quotationStatusApproved']['total'],
            $foreach['quotationStatusInprogress']['count'],
            $foreach['quotationStatusInprogress']['total'],
            $foreach['quotationStatusCompleted']['count'],
            $foreach['quotationStatusCompleted']['total'],
            $foreach['quotationStatusCancelled']['count'],
            $foreach['quotationStatusCancelled']['total']
        );

        /** output data */
        $outputData = (new ReturnService())->outputData($response, $countUserCompany);

        return response()->json($outputData, 200);
    }


    public function reportReceipt($request)
    {
        $perPage = $request->per_page;

        $query = Receipt::query();

        $totalReceipt = (clone $query)->count(); // count all invoices

        $receipt = (clone $query)->orderBy('receipts.id', 'asc')->paginate($perPage);

        $receipt = filterHelper::getReceipt($receipt); // Apply transformation

        $totalPrice = $receipt->sum('total');

        /** return data */
        $response = (new ReturnService())->returnReceipt($totalReceipt, $totalPrice, $receipt);

        return response()->json($response, 200);
    }

    public function reportCompanyCustomer($request)
    {
        $quotation = DB::table('quotations')
        ->select(
            DB::raw('(SELECT COUNT(id) FROM customers WHERE customers.id) as customers_count'),
            DB::raw('(SELECT COUNT(id) FROM users WHERE users.id) as user_count')
        )
        ->first();

        // $customer = $quotation->count('customers_count');
        // $user = $quotation->count('user_count');

        // return [
        //      $quotation
        //     //'user' => $user
        // ];


        // $queryCustomer = Customer::select('customers.*')->get();
        // $queryCompany = Company::select('companies.*')->get();

        // $customer = $queryCustomer->count();
        // $company = $queryCompany->count();

        // /** return data */
        // $response = (new ReturnService())->returnData($customer, $company);

         return response()->json($quotation, 200);
    }
}
