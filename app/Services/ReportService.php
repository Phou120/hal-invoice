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

        $countCompany = TableHelper::countCompany($quotationQuery); // count company
        $countUser = TableHelper::countUser($quotationQuery);   // count user

        $response = (new ReturnService())->response(
            $countCompany, $countUser, $totalBill, $totalPrice,
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

        return response()->json($response, 200);
    }


    public function reportReceipt($request)
    {
        $query = Receipt::query();

        $totalReceipt = (clone $query)->count(); // count all invoices

        $receipt = (clone $query)->orderBy('receipts.id', 'asc')->get();

        $receipt = filterHelper::getReceipt($receipt); // Apply transformation

        $totalPrice = $receipt->sum('total');

        /** return data */
        $response = (new ReturnService())->returnReceipt($totalReceipt, $totalPrice, $receipt);

        return response()->json($response, 200);
    }

    public function reportCompanyCustomer($request)
    {
        $queryCustomer = Customer::select('customers.*', DB::raw('(SELECT COUNT(*) FROM customers c WHERE c.id = customers.id) as customer_count'))->get();
        $queryCompany = Company::select('companies.*', DB::raw('(SELECT COUNT(*) FROM companies c WHERE c.id = companies.id) as company_count'))->get();

        $customer = $queryCustomer->count('customer_count');
        $company = $queryCompany->count('company_count');

        /** return data */
        $response = (new ReturnService())->returnData($customer, $company);

        return response()->json($response, 200);
    }
}
