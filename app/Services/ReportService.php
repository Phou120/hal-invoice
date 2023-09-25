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
        $invoiceQuery = Invoice::select('invoices.*');

        // filters date
        $invoiceQuery = FilterHelper::filterDate($invoiceQuery, $request);
        
        // Define invoice statuses
        $statuses = (new ReturnService())->status($invoiceQuery);

        // Initialize data array
        $responseData = (new ReturnService())->responseData($invoiceQuery);

        /** foreach data */
        $foreach = (new ReturnService())->foreachData($statuses, $invoiceQuery, $responseData);

        /** count user and company */
        $countUserCompany = (new ReturnService())->countUserCompany($invoiceQuery);

        if(!$countUserCompany){
            return response()->json(['msg' => 'ວັນທີບໍ່ມີໃນລະບົບ...'], 500);
        }

        /** output data  */
        $outputData = (new ReturnService())->outputData($foreach, $countUserCompany);

        return response()->json($outputData, 200);
    }

    public function reportQuotation($request)
    {
        // $quotationQuery = Quotation::query();
        $quotationQuery = DB::table('quotations')
        ->select(
            'quotations.*',
            DB::raw('(SELECT COUNT(id) FROM quotation_details WHERE quotation_details.quotation_id = quotations.id) as count_details'),
            DB::raw(
                '(SELECT SUM(quotationRate.total) FROM quotation_rates as quotationRate
                    JOIN currencies as currency ON quotationRate.currency_id = currency.id
                    WHERE quotationRate.quotation_id = quotations.id AND currency.name = "ກີບ") as total_sum_currency_1'
                ),
            DB::raw(
                '(SELECT SUM(quotationRate.total) FROM quotation_rates as quotationRate
                JOIN currencies as currency ON quotationRate.currency_id = currency.id
                WHERE quotationRate.quotation_id = quotations.id AND currency.name = "ບາດ") as total_sum_currency_2'
            ),
            DB::raw(
                '(SELECT SUM(quotationRate.total) FROM quotation_rates as quotationRate
                JOIN currencies as currency ON quotationRate.currency_id = currency.id
                WHERE quotationRate.quotation_id = quotations.id AND currency.name = "ໂດລາ") as total_sum_currency_3')
        )
        ->groupBy('quotations.id');

        // Apply filters to the query
        $quotationQuery = FilterHelper::quotationFilter($quotationQuery, $request);

        // Get the results as a collection
        $quotationCollection = $quotationQuery->get();

        // Count the total number of records
        $totalBill = $quotationCollection->sum('count_details'); // count all Quotations

        // Calculate the total price
        $totalPriceCurrencyKip = $quotationCollection->sum('total_sum_currency_1');
        $totalPriceCurrencyBaht = $quotationCollection->sum('total_sum_currency_2');
        $totalPriceCurrencyDollar = $quotationCollection->sum('total_sum_currency_3');

        $statuses = filterHelper::INVOICE_STATUS;

        $responseData = [];

        /** foreach  */
        // $foreach = (new ReturnService())->foreach($statuses, $quotationQuery, $responseData);
        foreach ($statuses as $status => $statusVariable) {
            $statusQuery = (clone $quotationQuery)
                ->where('status', FilterHelper::INVOICE_STATUS[$status])
                ->orderBy('quotations.id', 'asc')
                ->get();

            $statusCount = $statusQuery->count();
            $statusTotalCurrencyKip = $statusQuery->sum('total_sum_currency_1');
            $statusTotalCurrencyBaht = $statusQuery->sum('total_sum_currency_2');
            $statusTotalCurrencyDollar = $statusQuery->sum('total_sum_currency_3');

            $responseData[$statusVariable] = [
                'amount' => $statusCount,
                'totalCurrencyKip' => $statusTotalCurrencyKip,
                'totalCurrencyBaht' => $statusTotalCurrencyBaht,
                'totalCurrencyDollar' => $statusTotalCurrencyDollar,
            ];
        }
        // dd($foreach);
        /** count user and company */
        $countUserCompany = (new ReturnService())->countUserCompany($quotationQuery);

        if(!$countUserCompany){
            return response()->json(['msg' => 'ວັນທີບໍ່ມີໃນລະບົບ...'], 500);
        }
        /** output data */
        // $outputData = (new ReturnService())->outputData($response, $countUserCompany);

        return response()->json([
            'company_count' => $countUserCompany->company_count,
            'customer_count' => $countUserCompany->customer_count,
            'totalBill' => $totalBill,
            'totalPriceCurrencyKip' => $totalPriceCurrencyKip,
            'totalPriceCurrencyBaht' => $totalPriceCurrencyBaht,
            'totalPriceCurrencyDollar' => $totalPriceCurrencyDollar,
            'created' => $responseData['created'],
            'approved' => $responseData['approved'],
            'inprogress' => $responseData['inprogress'],
            'completed' => $responseData['completed'],
            'cancelled' => $responseData['cancelled'],
        ], 200);
    }

    public function reportReceipt($request)
    {
        $perPage = $request->per_page;

        $query = Receipt::select('receipts.*');

        // count all invoices
        $totalReceipt = (clone $query)->count();

        $totalPrice = $query->sum('total');

        $receipt = (clone $query)->orderBy('receipts.id', 'asc')->paginate($perPage);

        //$receipt = filterHelper::getReceipt($receipt); // Apply transformation

        $receipt->map(function ($item) {
            TableHelper::loopInvoice($item);
        });

        /** return data */
        $response = (new ReturnService())->returnReceipt($totalReceipt, $totalPrice, $receipt);

        return response()->json($response, 200);
    }

    public function reportCompanyCustomer($request)
    {
        $quotation = DB::table('quotations')->select(
            DB::raw('(SELECT COUNT(id) FROM companies) as company_count'),
            DB::raw('(SELECT COUNT(id) FROM customers) as customer_count')
        )->first();

        return response()->json($quotation, 200);
    }
}
