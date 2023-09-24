<?php

namespace App\Services\returnData;

use App\Models\User;
use App\Models\Company;
use App\Models\Currency;
use App\Traits\ResponseAPI;
use App\Helpers\TableHelper;
use App\Helpers\filterHelper;
use App\Models\Invoice;
use App\Models\InvoiceDetail;
use App\Models\QuotationRate;
use App\Models\QuotationDetail;
use Illuminate\Support\Facades\DB;

class ReturnService
{
    use ResponseAPI;

    public function status($invoiceQuery)
    {
        $invoiceQuery = [
            'created' => FilterHelper::INVOICE_STATUS['CREATED'],
            'approved' => FilterHelper::INVOICE_STATUS['APPROVED'],
            'inprogress' => FilterHelper::INVOICE_STATUS['INPROGRESS'],
            'completed' => FilterHelper::INVOICE_STATUS['COMPLETED'],
            'cancelled' => FilterHelper::INVOICE_STATUS['CANCELLED'],
        ];

        return $invoiceQuery;
    }

    public function countUserCompany($invoiceQuery)
    {
        return $invoiceQuery->select(
            DB::raw('(SELECT COUNT(id) FROM companies) as company_count'),
            DB::raw('(SELECT COUNT(id) FROM customers) as customer_count')
        )->first();
    }

    public function countQuotationDetail($quotation)
    {
        return $quotation->select(
            DB::raw('(SELECT COUNT(id) FROM quotation_details) as count_detail')
        )->first();
    }

    public function outputData($foreach, $countUserCompany)
    {
        $output = [
            "company_count" => $countUserCompany->company_count,
            "customer_count" => $countUserCompany->customer_count,
        ] + $foreach;

        return $output;
    }

    public function responseData($quotationQuery)
    {
        $responseData = [
            'totalBill' => $quotationQuery->count(),
            'totalPrice' => 0,
        ];

        return $responseData;
    }

    public function selectQuotation($quotationDetailId)
    {
        $quotation = DB::table('quotations')
        ->select('quotation_detail.*')
        ->join('quotation_details as quotation_detail', 'quotation_detail.quotation_id', '=', 'quotations.id')
        ->where('quotation_detail.id', $quotationDetailId)
        ->first();

        return $quotation;
    }

    /** select quotationRate */
    public function selectQuotationRate($quotationDetailId)
    {
        $getQuotationRate = DB::table('quotation_rates')
            ->select('quotation_rates.*')
            ->join('quotations', 'quotation_rates.quotation_id', '=', 'quotations.id')
            ->join('quotation_details', 'quotation_details.quotation_id', '=', 'quotations.id')
            ->where('quotation_details.id', $quotationDetailId) // Use $getQuotation->id
            ->first();

        return $getQuotationRate;
    }

    public function invoiceDetail($quotation, $invoiceId)
    {
        $invoiceDetails = [
            'description' => $quotation->description,
            'invoice_id' => $invoiceId,
            'amount' => $quotation->amount,
            'price' => $quotation->price,
            'order' => $quotation->order,
            'name' => $quotation->name,
            'total' => $quotation->amount * $quotation->price,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        return $invoiceDetails;
    }

    public function filterQuotationStatus($query, $status)
    {
        return $query->where('status', filterHelper::INVOICE_STATUS[$status]);
    }

    /** loop report quotations.status */
    public function foreach($statuses, $quotationQuery, $responseData)
    {
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

        return $responseData;
    }

    public function quotationMap($quotation, $currencyTotals, $totalDetail)
    {
        $quotation->map(function ($item) use (&$currencyTotals, &$totalDetail) {
            // Calculate the count of details associated with the quotation
            $item->countDetail = QuotationDetail::where('quotation_id', $item->id)->count();
            $totalDetail += $item->countDetail;
            // Retrieve the rates associated with the quotation
            $quotationRates = QuotationRate::where('quotation_id', $item->id)->get();

            // Iterate over the rates and accumulate currency totals
            $quotationRates->each(function ($rate) use (&$currencyTotals) {
                $currencyId = $rate->currency_id;
                $currency = Currency::find($currencyId);

                if ($currency) {
                    $currencyName = $currency->short_name;

                    if (!isset($currencyTotals[$currencyId])) {
                        $currencyTotals[$currencyId] = [
                            'currency' => $currencyName,
                            'rate' => 0,
                            'total' => 0,
                        ];
                    }

                    $currencyTotals[$currencyId]['rate'] += $rate->rate;
                    $currencyTotals[$currencyId]['total'] += $rate->total;
                    // dd($currencyTotals[$currencyId]);?
                }
            });
        });

        $rateCurrencies = collect($currencyTotals)->sortByDesc('rate');

        return $rateCurrencies;
    }
    /** loop quotation data */
    public function foreachData($statuses, $quotationQuery, $responseData)
    {
        foreach ($statuses as $statusName => $statusValue) {
            $statusQuery = clone $quotationQuery;
            $statusQuery->where('status', $statusValue);

            $statusQuotation = $statusQuery->orderBy('id', 'asc')->get();
            $statusQuotation = FilterHelper::getTotal($statusQuotation);

            $responseData[$statusName] = [
                'amount' => $statusQuotation->count(),
                'total' => $statusQuotation->sum('total'),
            ];

            $responseData['totalPrice'] += $responseData[$statusName]['total'];
        }

        return $responseData;
    }

    /** join data */
    public function joinData($request)
    {
        $editDetail = DB::table('quotation_details')
        ->select('quotation_details.*', 'quotation_types.rate', 'quotations.quotation_type_id')
        ->join('quotations', 'quotation_details.quotation_id', '=', 'quotations.id')
        ->join('quotation_types', 'quotations.quotation_type_id', '=', 'quotation_types.id')
        ->where('quotation_details.id', $request['id'])
        ->first();

        return $editDetail;
    }

    /** update data in quotation_detail */
    public function updateData($editDetail, $request)
    {
         // Update the fields
         $updateData = [
            'order' => $request['order'],
            'name' => $request['name'],
            'hour' => $request['hour'],
            'description' => $request['description'],
            'rate' => $editDetail->rate,
            'total' => $request['hour'] * $editDetail->rate
        ];

        return $updateData;
    }

    /** add quotation_details */
    public function detailData($request)
    {
        $data = [
            'order' => $request['order'],
            'quotation_id' => $request['id'],
            'name' => $request['name'],
            'hour' => $request['hour'],
            'description' => $request['description'],
            'created_at' => now(),
            'updated_at' => now(),
        ];

        return $data;
    }

    /** join data in quotation_type to get quotation_types.rate */
    public function getQuotationRate($request)
    {
        $getQuotationType = DB::table('quotation_rates')
            ->select('quotation_rates.rate')
            ->join('quotations as quotation', 'quotation_rates.quotation_id', '=', 'quotation.id')
            ->join('quotation_details as quotationDetail', 'quotationDetail.quotation_id', '=', 'quotation.id')
            ->where('quotationDetail.quotation_id', '=', $request['id'])
            // ->whereNotNull('quotation.quotation_type_id')
            ->whereNotNull('quotation_rates.rate')
            ->first();

        return $getQuotationType;
    }

    /** return user data */
    public function returnUserData($listUser, $roleUser, $permissionRole)
    {
        return [
            'user' => [
                'id' => $listUser->id,
                'name' => $listUser->name,
                'email' => $listUser->email,
                'profile_url' => $listUser->profile_url,
                'tel' => $listUser->tel,
                'created_at' => $listUser->created_at,
                'updated_at' => $listUser->updated_at,
                'roleUser' => $roleUser,
                'permissionRole' => $permissionRole,
            ]
        ];
    }

    /** return receipt data */
    public function returnDataReceipt($totalBill, $totalPrice, $listReceipt)
    {
        return [
            'totalBill' => $totalBill,
            'totalPrice' => $totalPrice,
            'listReceipt' => $listReceipt
        ];
    }

    /** return receipt */
    public function returnReceipt($totalReceipt, $totalPrice, $receipt)
    {
        return [
            'totalReceipt' => $totalReceipt,
            'totalPrice' => $totalPrice,
            'query' => $receipt
        ];
    }

    /** return data in company and customer */
    public function returnData($company, $customer)
    {
        return[
            'customer' => $customer,
            'company' => $company
        ];
    }

    /** response invoice data */
    public function responseInvoiceData(
        $totalBill, $totalPrice, $created, $createdTotal, $approved, $approvedTotal,
        $inprogress, $inprogressTotal, $completed, $completedTotal, $canceled, $canceledTotal, $listInvoice
    )
    {
        return [
            'totalBill' => $totalBill,
            'totalPrice' => $totalPrice,
            'created' => [
                'amount' => $created,
                'total' => $createdTotal,
             ],
             'approved' => [
                'amount' => $approved,
                'total' => $approvedTotal,
             ],
            'inprogress' => [
               'amount' => $inprogress,
               'total' => $inprogressTotal,
            ],
            'completed' => [
                'amount' => $completed,
                'total' => $completedTotal,
             ],
             'cancelled' => [
                'amount' => $canceled,
                'total' => $canceledTotal,
             ],
            'listInvoice' => $listInvoice
        ];
    }

    /** response report quotation data */
    public function response(
        $totalBill, $totalPriceCurrencyKip,$totalPriceCurrencyBaht,
        $totalPriceCurrencyDollar, $created,$createdTotal, $approved,
        $approvedTotal,$inprogress, $inprogressTotal,$completed, $completedTotal, $cancelled,$cancelledTotal
    )
    {
        // dd($createdTotal);
        return [
            'totalBill' => $totalBill,
            'totalPriceCurrencyKip' => $totalPriceCurrencyKip,
            'totalPriceCurrencyBaht' => $totalPriceCurrencyBaht,
            'totalPriceCurrencyDollar' => $totalPriceCurrencyDollar,
            'created' => [
                'amount' => $created,
                'total' => $createdTotal,
             ],
             'approved' => [
                'amount' => $approved,
                'total' => $approvedTotal,
             ],
            'inprogress' => [
               'amount' => $inprogress,
               'total' => $inprogressTotal,
            ],
            'completed' => [
                'amount' => $completed,
                'total' => $completedTotal,
             ],
             'cancelled' => [
                'amount' => $cancelled,
                'total' => $cancelledTotal,
             ],
        ];
    }

    /** check Role To Use Paginate in invoice */
    public function checkRoleToUsePaginateInvoice($user, $query)
    {
        if ($user->hasRole(['superadmin', 'admin'])) {
            $listInvoice = $query->orderBy('invoices.id', 'asc');
        }

        if ($user->hasRole(['company-admin', 'company-user'])) {
            $listInvoice = $query
                ->where(function ($query) use ($user) {
                    $query->where('invoices.created_by', $user->id);
                })
                ->orderBy('invoices.id', 'asc');
        }

        return $listInvoice;
    }

    /** check Role To Use Paginate in quotation */
    public function checkRoleToUsePaginate($query, $user)
    {
        if ($user->hasRole(['superadmin', 'admin'])) {
            $listQuotations = $query->orderBy('quotations.id', 'asc');
        }

        if ($user->hasRole(['company-admin', 'company-user'])) {
            $listQuotations = $query
                ->where(function ($query) use ($user) {
                    $query->where('quotations.created_by', $user->id);
                })
                ->orderBy('quotations.id', 'asc');
        }

        return $listQuotations;
    }

    /** update type_quotation in invoice */
    public function updateTypeQuotationInInvoice($getQuotation, $addInvoice)
    {
        if($getQuotation){
            $addInvoice->type_quotation = 1;
            $addInvoice->save();
        }
    }

    /** map data in quotation */
    public function mapDataInQuotation($listQuotations)
    {
        $listQuotations->map(function ($item) {
            TableHelper::loopDataInQuotation($item);
        });

        return $listQuotations;
    }

    /** check role in quotation */
    public function checkRole($detailsQuery, $user)
    {
        if ($user->hasRole(['superadmin', 'admin'])) {
            $detailsQuery->orderBy('quotations.id', 'asc');
        } elseif ($user->hasRole(['company-admin', 'company-user'])) {
            $detailsQuery->where('quotations.created_by', $user->id)
                ->orderBy('quotations.id', 'asc');
        }
    }

    /** check role in Invoice */
    public function checkRoleInvoice($detailsQuery, $user)
    {
        if ($user->hasRole(['superadmin', 'admin'])) {
            $detailsQuery->orderBy('invoices.id', 'asc');
        } elseif ($user->hasRole(['company-admin', 'company-user'])) {
            $detailsQuery->where('invoices.created_by', $user->id)
                ->orderBy('invoices.id', 'asc');
        }
    }

    /** query quotationDetail */
    public function detailsQuery($request)
    {
        $detailsQuery = QuotationDetail::select('quotation_details.*')
            ->join('quotations', 'quotation_details.quotation_id', 'quotations.id')
            ->where('quotation_id', $request->id);

        return $detailsQuery;
    }

    /** query invoiceDetail */
    public function invoiceDetailsQuery($request)
    {
        $detailsQuery = InvoiceDetail::select('invoice_details.*')
            ->join('invoices', 'invoice_details.invoice_id', 'invoices.id')
            ->where('invoice_id', $request->id);

        return $detailsQuery;
    }

    /** sum rate and sum total in quotationRate */
    public function currencyTotals($quotationRates)
    {
        $currencyTotals = $quotationRates->groupBy('currency_id')->map(function ($group) {
            $currency = DB::table('currencies')->where('id', $group->first()->currency_id)->first();

            return [
                'currency' => $currency->short_name,
                'rate' => $group->sum('rate'),
                'total' => $group->sum('total'),
            ];
        });

        return $currencyTotals;
    }

    /** count quotation detail */
    public function countDetail($request)
    {
        $countDetail = DB::table('quotation_details')->where('quotation_id', $request->id)->count();

        return $countDetail;
    }

    /** count invoice detail */
    public function countDetailInvoice($request)
    {
        $countDetail = DB::table('invoice_details')->where('invoice_id', $request->id)->count();

        return $countDetail;
    }

    /** where quotation id */
    public function quotationRate($request)
    {
        $quotationRates = DB::table('quotation_rates')->where('quotation_id', $request->id)->get();

        return $quotationRates;
    }

    /** where quotation id */
    public function invoiceRate($request)
    {
        $invoiceRates = DB::table('invoice_rates')->where('invoice_id', $request->id)->get();

        return $invoiceRates;
    }

    /** return quotation data */
    public function quotationData(
        $totalDetail, $statusTotals, $rateCurrencies, $listQuotations
    )
    {
        return [
            'totalDetail' => $totalDetail,
            'rate' => $rateCurrencies->values()->all(),
            'created' => $statusTotals['created'],
            'approved' => $statusTotals['approved'],
            'inprogress' => $statusTotals['inprogress'],
            'completed' => $statusTotals['completed'],
            'cancelled' => $statusTotals['cancelled'],
            'listQuotations' => $listQuotations
        ];
    }

    public function outputInvoiceData($countDetail,$rateCurrencies, $mapInvoice , $details)
    {
        return [
            'countDetail' => $countDetail,
            'rate' => $rateCurrencies,
            'invoice' => $mapInvoice,
            'details' => $details,
        ];
    }

    /** return invoice data */
    public function invoiceData(
        $totalDetail, $statusTotals, $rateCurrencies, $mapInvoice
    )
    {
        return [
            'totalDetail' => $totalDetail,
            'rate' => $rateCurrencies,
            'created' => $statusTotals['created'],
            'approved' => $statusTotals['approved'],
            'inprogress' => $statusTotals['inprogress'],
            'completed' => $statusTotals['completed'],
            'cancelled' => $statusTotals['cancelled'],
            'listQuotations' => $mapInvoice
        ];
    }
}
