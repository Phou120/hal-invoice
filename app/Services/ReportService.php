<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Receipt;
use App\Models\Currency;
use App\Models\Quotation;
use App\Models\InvoiceRate;
use App\Traits\ResponseAPI;
use App\Helpers\TableHelper;
use App\Helpers\filterHelper;
use App\Models\InvoiceDetail;
use App\Models\QuotationDetail;
use App\Models\QuotationRate;
use Illuminate\Support\Facades\DB;
use App\Services\returnData\ReturnService;

class ReportService
{
    use ResponseAPI;

    public $returnService;

    public function __construct(ReturnService $returnService)
    {
        $this->returnService = $returnService;
    }

    /** report invoice */
    public function reportInvoice($request)
    {
        // Define an array to store currency totals
        $currencyTotals = [];

        // Define invoice statuses
        $statuses = filterHelper::INVOICE_STATUS;

        // Initialize status data
        $statusData = array_fill_keys($statuses, [
            'totalDetail' => 0,
            'rates' => [],
        ]);

        // Get all invoices with date filtering
        $invoices = DB::table('invoices')
            ->select('invoices.*',
                DB::raw('(SELECT COUNT(id) FROM companies) as company_count'),
                DB::raw('(SELECT COUNT(id) FROM customers) as customer_count')
            )
            ->orderBy('invoices.id', 'asc')
            ->when($request->has('start_date') && $request->has('end_date'), function ($query) use ($request) {
                FilterHelper::filterDate($query, $request);
            })->get();

        foreach ($invoices as $invoice) {
            // Count details for each invoice
            $invoice->countDetail = DB::table('invoice_details')
                ->where('invoice_id', $invoice->id)
                ->whereNull('deleted_at')
                ->count();

            // Calculate currency totals for the invoice
            $invoiceRates = DB::table('invoice_rates')
                ->where('invoice_id', $invoice->id)
                ->get();

                if ($invoice->countDetail === 0) {
                    continue;
                }

            foreach ($invoiceRates as $rate) {
                $currencyId = $rate->currency_id;
                $currency = DB::table('currencies')->find($currencyId);

                if ($currency) {
                    $currencyName = $currency->short_name;

                    // Initialize currency totals if not exists
                    if (!isset($currencyTotals[$currencyName])) {
                        $currencyTotals[$currencyName] = [
                            'currency' => $currencyName,
                            'rate' => 0,
                            'total' => 0,
                        ];
                    }

                    $currencyTotals[$currencyName]['rate'] += $rate->rate;
                    $currencyTotals[$currencyName]['total'] += $rate->total;
                }
            }

            // Update status totals
            $invoiceStatus = in_array($invoice->status, $statuses) ? $invoice->status : 'unknown';

            $statusData[$invoiceStatus]['totalDetail'] += $invoice->countDetail;

            // Calculate rates for the current status
            foreach ($invoiceRates as $rate) {
                $currencyId = $rate->currency_id;
                $currency = DB::table('currencies')->find($currencyId);

                if ($currency) {
                    $currencyName = $currency->short_name;

                    // Initialize status totals if not exists
                    if (!isset($statusData[$invoiceStatus]['rates'][$currencyName])) {
                        $statusData[$invoiceStatus]['rates'][$currencyName] = [
                            'currency' => $currencyName,
                            'rate' => 0,
                            'total' => 0,
                        ];
                    }

                    // Update status totals
                    $statusData[$invoiceStatus]['rates'][$currencyName]['rate'] += $rate->rate;
                    $statusData[$invoiceStatus]['rates'][$currencyName]['total'] += $rate->total;
                }
            }
        }

        foreach ($statuses as $status) {
            if ($statusData[$status]['totalDetail'] === 0) {
                $statusData[$status]['rates'] = [];
            }
        }

        // Filter out currencies with zero rates for each status
        foreach ($statusData as &$status) {
            if (is_array($status) && isset($status['rates'])) {
                $status['rates'] = array_values(array_filter($status['rates'], function ($currency) {
                    return is_array($currency) && isset($currency['rate']);
                }));
            }
        }

        // Sort currencyTotals by rate in descending order and limit to the top three
        $rateCurrencies = collect($currencyTotals)->values()->all();

        // Create the main response object
        $responseData = [
            'company_count' => $invoices->isEmpty() ? 0 : $invoices[0]->company_count,
            'customer_count' => $invoices->isEmpty() ? 0 : $invoices[0]->customer_count,
            'totalDetail' => array_sum(array_column($statusData, 'totalDetail')),
            'rate' => $rateCurrencies,
        ] + $statusData; // Merge status data into the main response object

        return response()->json($responseData, 200);
    }

    /** report quotation */
    public function reportQuotation($request)
    {
        // Define an array to store currency totals
        $currencyTotals = [];

        // Define invoice statuses
        $statuses = filterHelper::INVOICE_STATUS;

        // Initialize status data
        $statusData = array_fill_keys($statuses, [
            'totalDetail' => 0,
            'rates' => [],
        ]);

        // Get all invoices with date filtering
        $query = Quotation::select(
            'quotations.*',
            DB::raw('(SELECT COUNT(id) FROM companies) as company_count'),
            DB::raw('(SELECT COUNT(id) FROM customers) as customer_count')
        );

            /** filter date */
        FilterHelper::quotationFilter($query, $request);

        $quotations = $query ->orderBy('quotations.id', 'asc')->get();

        foreach ($quotations as $item) {
            // Count details for each invoice
            $item->countDetail = QuotationDetail::where('quotation_id', $item->id)
                ->whereNull('deleted_at')->count();

            // Calculate currency totals for the invoice
            $quotationRates = QuotationRate::where('quotation_id', $item->id)->get();

                if ($item->countDetail === 0) {
                    continue;
                }

            foreach ($quotationRates as $rate) {
                $currencyId = $rate->currency_id;
                $currency = Currency::find($currencyId);

                if ($currency) {
                    $currencyName = $currency->short_name;

                    // Initialize currency totals if not exists
                    if (!isset($currencyTotals[$currencyName])) {
                        $currencyTotals[$currencyName] = [
                            'currency' => $currencyName,
                            'rate' => 0,
                            'total' => 0,
                        ];
                    }

                    $currencyTotals[$currencyName]['rate'] += $rate->rate;
                    $currencyTotals[$currencyName]['total'] += $rate->total;
                }
            }

            // Update status totals
            $quotationStatus = in_array($item->status, $statuses) ? $item->status : 'unknown';

            $statusData[$quotationStatus]['totalDetail'] += $item->countDetail;

            // Calculate rates for the current status
            foreach ($quotationRates as $rate) {
                $currencyId = $rate->currency_id;
                $currency = Currency::find($currencyId);

                if ($currency) {
                    $currencyName = $currency->short_name;

                    // Initialize status totals if not exists
                    if (!isset($statusData[$quotationStatus]['rates'][$currencyName])) {
                        $statusData[$quotationStatus]['rates'][$currencyName] = [
                            'currency' => $currencyName,
                            'rate' => 0,
                            'total' => 0,
                        ];
                    }

                    // Update status totals
                    $statusData[$quotationStatus]['rates'][$currencyName]['rate'] += $rate->rate;
                    $statusData[$quotationStatus]['rates'][$currencyName]['total'] += $rate->total;
                }
            }
        }

        foreach ($statuses as $status) {
            if ($statusData[$status]['totalDetail'] === 0) {
                $statusData[$status]['rates'] = [];
            }
        }

        // Filter out currencies with zero rates for each status
        foreach ($statusData as &$status) {
            if (is_array($status) && isset($status['rates'])) {
                $status['rates'] = array_values(array_filter($status['rates'], function ($currency) {
                    return is_array($currency) && isset($currency['rate']);
                }));
            }
        }
        // Sort currencyTotals by rate in descending order and limit to the top three
        $rateCurrencies = collect($currencyTotals)->values()->all();

        // Create the main response object
        if (!empty($item)) {
            $responseData = [
                'company_count' => $item->company_count ?? 0,
                'customer_count' => $item->customer_count ?? 0,
                'totalDetail' => array_sum(array_column($statusData, 'totalDetail')),
                'rate' => $rateCurrencies,
            ] + $statusData;
        } else {
            $responseData = [
                'company_count' => 0,
                'customer_count' => 0,
                'totalDetail' => array_sum(array_column($statusData, 'totalDetail')),
                'rate' => $rateCurrencies,
            ] + $statusData;
        } // Merge status data into the main response object

        return response()->json($responseData, 200);
    }

    /** report receipt */
    public function reportReceipt($request)
    {
        // $perPage = $request->per_page;

        $query = Receipt::select('receipts.*');

        /** filter date */
        FilterHelper::receiptFilter($query, $request);

        $receipts = $query->orderBy('receipts.id', 'asc')->get();

        // Initialize currency totals array
        $currencyTotals = [];

        foreach ($receipts as $item) { // Use get() to fetch the results of the query
            // Calculate currency totals for the invoice
            $invoiceRates = InvoiceRate::where('id', $item->invoice_rate_id)->get();
            // return $invoiceRates;

            foreach ($invoiceRates as $rate) {
                $currencyId = $rate->currency_id;
                $currency = Currency::find($currencyId);
                // return $currency;

                if ($currency) {
                    $currencyShortName = $currency->short_name;

                    // Initialize currency totals if not exists
                    if (!isset($currencyTotals[$currencyShortName])) {
                        $currencyTotals[$currencyShortName] = [
                            'currency' => $currencyShortName,
                            'rate' => 0,
                            'total' => 0,
                        ];
                    }

                    // Use the currencyName variable to update the totals
                    $currencyTotals[$currencyShortName]['rate'] += $rate->rate;
                    $currencyTotals[$currencyShortName]['total'] += $rate->total;
                }
            }
        }

        // Convert currencyTotals array to a list
        $rateCurrencies = array_values($currencyTotals);

        /** count receipt */
        $countReceipt = $query->count();

        /** merge data */
        $response = [
            'totalReceipt' => $countReceipt,
            'rate' => $rateCurrencies
        ];

        return response()->json($response, 200);
    }

    public function reportCompanyCustomer()
    {
        $quotation = DB::table('quotations')->select(
            DB::raw('(SELECT COUNT(id) FROM companies) as company_count'),
            DB::raw('(SELECT COUNT(id) FROM customers) as customer_count')
        )->first();

        return response()->json($quotation, 200);
    }
}
