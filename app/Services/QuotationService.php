<?php

namespace App\Services;

use App\Models\User;
use App\Models\Currency;
use App\Models\Quotation;
use App\Models\CompanyUser;
use App\Traits\ResponseAPI;
use App\Helpers\TableHelper;
use App\Helpers\filterHelper;
use App\Models\InvoiceDetail;
use App\Models\QuotationRate;
use App\Models\QuotationType;
use App\Helpers\generateHelper;
use App\Models\QuotationDetail;
use App\Services\CalculateService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Services\returnData\ReturnService;
use App\Services\GetData\GetDataOfQuotationService;

class QuotationService
{
    use ResponseAPI;

    public $calculateService;
    public $getDataOfQuotationService;
    public $returnService;

    public function __construct(
        ReturnService $returnService,
        CalculateService  $calculateService,
        GetDataOfQuotationService $getDataOfQuotationService
    )
    {
        $this->returnService = $returnService;
        $this->calculateService = $calculateService;
        $this->getDataOfQuotationService = $getDataOfQuotationService;
    }


    /** add quotation */
    public function addQuotation($request)
    {
        DB::beginTransaction();
            /** add quotation */
            $addQuotation = new Quotation();
            $addQuotation->quotation_number = generateHelper::generateQuotationNumber('QT- ', 8);
            $addQuotation->quotation_name = $request['quotation_name'];
            $addQuotation->start_date = $request['start_date'];
            $addQuotation->end_date = $request['end_date'];
            $addQuotation->note = $request['note'];
            $addQuotation->customer_id = $request['customer_id'];
            $addQuotation->quotation_type_id = $request['quotation_type_id'];
            $addQuotation->created_by = Auth::user('api')->id;
            $addQuotation->save();

            /** add detail */
            $totalHours = 0;

            if(!empty($request['quotation_details'])){
                foreach($request['quotation_details'] as $item){
                    $addDetail = new QuotationDetail();
                    $addDetail->order = $item['order'];
                    $addDetail->quotation_id = $addQuotation['id'];
                    $addDetail->name = $item['name'];
                    $addDetail->hour = $item['hour'];
                    $addDetail->description = $item['description'];
                    $addDetail->save();

                    $totalHours += $item['hour'];
                }
            }

            /** create quotation_rate */
            $getCurrencies = Currency::orderBy('id', 'desc')->get();
            if(count($getCurrencies) > 0) {
                foreach($getCurrencies as $item) {
                    $addQuotationRate = new QuotationRate();
                    $addQuotationRate->quotation_id = $addQuotation['id'];
                    $addQuotationRate->currency_id = $item['id'];
                    $addQuotationRate->rate = $item['rate'];
                    $addQuotationRate->sub_total = $totalHours * $item['rate'];
                    $addQuotationRate->discount = $request['discount'];
                    $addQuotationRate->save();

                    /**Calculate */
                    $this->calculateService->calculateTotal($request, $addQuotationRate['sub_total'], $addQuotationRate['id']);
                }
            }

        DB::commit();

        return response()->json([
            'error' => false,
            'msg' => 'ສຳເລັດແລ້ວ'
        ], 200);
    }

    /** list quotation */
    public function listQuotations($request)
    {
        $perPage = $request->per_page;
        $user = Auth::user();

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
        $query = DB::table('quotations')
            ->select('quotations.*',
                DB::raw('(SELECT COUNT(id) FROM companies) as company_count'),
                DB::raw('(SELECT COUNT(id) FROM customers) as customer_count')
            );

        /** filter date */
        FilterHelper::quotationFilter($query, $request);

        $quotations = $query->orderBy('quotations.id', 'asc')->get();

        foreach ($quotations as $quotation) {
            // Count details for each invoice
            $quotation->countDetail = DB::table('quotation_details')
                ->where('quotation_id', $quotation->id)
                ->whereNull('deleted_at')
                ->count();

            // Calculate currency totals for the invoice
            $quotationRates = DB::table('quotation_rates')
                ->where('quotation_id', $quotation->id)
                ->get();

                if ($quotation->countDetail === 0) {
                    continue;
                }

            foreach ($quotationRates as $rate) {
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

                    /** sum rate and total in QuotationRate */
                    $currencyTotals[$currencyName]['rate'] += $rate->rate;
                    $currencyTotals[$currencyName]['total'] += $rate->total;
                }
            }

            // Update status totals
            $invoiceStatus = in_array($quotation->status, $statuses) ? $quotation->status : 'unknown';

            $statusData[$invoiceStatus]['totalDetail'] += $quotation->countDetail;

            // Calculate rates for the current status
            foreach ($quotationRates as $rate) {
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

        // /** filter status */
        filterHelper::filterStatus($query, $request);

        /** filter DI */
        filterHelper::filterID($query, $request);

        //  /** filter Name */
        filterHelper::filterQuotationName($query, $request);

        if ($user->hasRole(['superadmin', 'admin'])) {
            // Allow superadmin and admin to see all data
            $listQuotation = $query->orderBy('quotations.id', 'desc');

            $getQuotation = $listQuotation->paginate($perPage);

            // Map data
            $mapQuotation = $this->returnService->mapDataInQuotation($getQuotation);

            /** Merge quotation data of super-admin and admin */
            $responseData = [
                'company_count' => $quotations->isEmpty() ? 0 : $quotations[0]->company_count,
                'customer_count' => $quotations->isEmpty() ? 0 : $quotations[0]->customer_count,
                'totalDetail' => array_sum(array_column($statusData, 'totalDetail')),
                'rate' => $rateCurrencies,
            ] + $statusData + [
                'listInvoice' => $mapQuotation,
            ];

            return response()->json($responseData, 200);
        }

        if ($user->hasRole(['company-admin', 'company-user'])) {
            // Filter invoices for company-admin and company-user based on user ID
            $listQuotation = $query->where('created_by', $user->id)->orderBy('quotations.id', 'asc');

            $getQuotation = $listQuotation->paginate($perPage);

            // Map data
            $mapQuotation = $this->returnService->mapDataInQuotation($getQuotation);

            /** Merge quotation data of user */
            $responseData = [
                'totalDetail' => array_sum(array_column($statusData, 'totalDetail')),
                'rate' => $rateCurrencies,
            ] + $statusData + [
                'listInvoice' => $mapQuotation,
            ];

            return response()->json($responseData, 200);
        }
    }

    public function listQuotation($request)
    {
        $rateId = $request->id;
        $currencyId = $request->currency_id;

        $quotationRate = QuotationRate::find($rateId);

        $currency = Currency::find($currencyId);

        if ($currency === null) {
            return response()->json('currency name not found...', 422); // or handle the error as needed
        }

        $name = $currency->name;

        $invoice = Quotation::select([
            'quotations.*',
            'currencies.name as currencyName',
            'currencies.short_name as currencyShortName',
            'quotation_rates.sub_total as rateSubTotal',
            'quotation_rates.discount as rateDiscount',
            'quotation_rates.tax as rateTax',
            'quotation_rates.total as rateTotal',
            'quotation_rates.rate as rate',
            DB::raw('(SELECT COUNT(*) FROM quotation_details WHERE quotation_details.quotation_id = quotations.id) as count_details')
        ])
        ->join('quotation_rates', 'quotation_rates.quotation_id', 'quotations.id')
        ->join('currencies', 'quotation_rates.currency_id', 'currencies.id')
        ->where('quotation_rates.quotation_id', $quotationRate->quotation_id)
        ->where('currencies.name', $name)
        ->orderBy('id', 'desc')
        ->first();

        return $invoice->format();
    }

    /** add quotation detail */
    public function addQuotationDetail($request)
    {
        $hour = $request['hour'];

        DB::beginTransaction();

            $quotationDetail = new QuotationDetail();
            $quotationDetail->quotation_id = $request['id'];
            $quotationDetail->order = $request['order'];
            $quotationDetail->name = $request['name'];
            $quotationDetail->hour = $hour;
            $quotationDetail->description = $request['description'];
            $quotationDetail->save();

            /** update created_by in quotation */
            filterHelper::updateCreatedByInQuotation($quotationDetail);

            // Find the QuotationRate records with the matching quotation_id
            $quotationRates = QuotationRate::where('quotation_id', $request['id'])->get();

            /** calculate */
            $this->calculateService->calculateAndUpdateQuotationRates($quotationRates, $hour);

        DB::commit();

        return response()->json([
            'error' => false,
            'msg' => 'ສຳເລັດແລ້ວ'
        ], 200);
    }

    public function listQuotationDetail($request)
    {
        $user = Auth::user();

        $itemQuery = DB::table('quotations')->select('quotations.*')->where('quotations.id', $request->id);

        // Check user roles and apply appropriate conditions
        $this->returnService->checkRole($itemQuery, $user);

        $item = $itemQuery->orderBy('quotations.id', 'asc')->get();

        // Get the count of quotation details
        $countDetail = $this->returnService->countDetail($request);

        // Get quotation rates for the given quotation
        $quotationRates = $this->returnService->quotationRate($request);
        // Use collections to group by currency and calculate sums
        $currencyTotals = $this->returnService->currencyTotals($quotationRates);
        // Sort the resulting collection by rate in descending order
        $rateCurrencies = $currencyTotals->sortByDesc('rate')->values()->toArray();

        if ($countDetail === 0) {
            $rateCurrencies = []; // Set rate to an empty array if countDetail is 0
        }

        // Detail query
        $detailsQuery = $this->returnService->detailsQuery($request);
        // Check user roles and apply appropriate conditions
        $this->returnService->checkRole($detailsQuery, $user);

        // Get the results
        $details = $detailsQuery->get();

        /** Merge data */
        $responseData = [
            'countDetail' => $countDetail,
            'rate' => $rateCurrencies,
            'quotation' => $item,
            'details' => $details
        ];

        return response()->json($responseData, 200);
    }

    /** edit quotation */
    public function editQuotation($request)
    {
        DB::beginTransaction();

            $editQuotation = Quotation::find($request['id']);

            if ($editQuotation) {
                /** get request to front-end */
                $editQuotations = (new GetDataOfQuotationService())->getData($editQuotation, $request);

                // Assuming you have a relationship like 'quotationRate' defined in your Quotation model
                $getQuotationRate = QuotationRate::where('quotation_id', $editQuotations->id)->get();

                /** calculate */
                $this->calculateService->UpdateQuotationRates($getQuotationRate, $request);
            }

        DB::commit();

        return response()->json([
            'error' => false,
            'msg' => 'ສຳເລັດແລ້ວ'
        ], 200);
    }

    /** edit detail */
    public function editQuotationDetail($request)
    {
        DB::beginTransaction();

        try {
            $hour = $request['hour'];
            // Update QuotationDetail
            $quotationDetail = QuotationDetail::find($request['id']);
            $quotationDetail->order = $request['order'];
            $quotationDetail->name = $request['name'];
            $quotationDetail->hour = $hour;
            $quotationDetail->description = $request['description'];
            $quotationDetail->save();

            /** update quotation column updated_by */
            $this->getDataOfQuotationService->getDataQuotation($quotationDetail);

            // Calculate and update QuotationRates
            $quotationID = QuotationDetail::where('quotation_id', $quotationDetail->quotation_id)->sum('hour');
            $quotationRates = QuotationRate::where('quotation_id', $quotationDetail->quotation_id)->get();

            /** calculate */
            $this->calculateService->updateQuotationDetailAndQuotationRate($quotationRates, $quotationID);

            DB::commit();

            return response()->json([
                'error' => false,
                'msg' => 'Success'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => true,
                'msg' => 'not found...'
            ], 500);
        }
    }

    /** delete detail */
    public function deleteQuotationDetail($request)
    {
        DB::beginTransaction();
            $deleteDetail = QuotationDetail::find($request['id']);
            $quotation = $deleteDetail->quotation_id;

            // Delete the QuotationDetail
            $deleteDetail->delete();

            /** update quotation column updated_by */
            $this->getDataOfQuotationService->getDataQuotation($deleteDetail);

            // Update the sum of hours for the Quotation
            $quotationID = QuotationDetail::where('quotation_id', $quotation)->sum('hour');
            // Get the QuotationRates related to this quotation
            $quotationRates = QuotationRate::where('quotation_id', $quotation)->get();

            /** calculate */
            $this->calculateService->updateQuotationDetailAndQuotationRate($quotationRates, $quotationID);

        DB::commit();
         return response()->json([
            'error' => false,
            'msg' => 'ສຳເລັດແລ້ວ'
        ], 200);
    }

    public function deleteQuotation($request)
    {
        try {
            DB::beginTransaction();

                $quotationId = $request->id;

                // Find the Quotation model
                $quotation = Quotation::findOrFail($quotationId);
                $quotation->updated_by = Auth::user('api')->id;
                $quotation->save();

                // Delete the Quotation and related records
                $quotation->delete();
                QuotationRate::where('quotation_id', $quotationId)->delete();
                QuotationDetail::where('quotation_id', $quotationId)->delete();

            DB::commit();

            return response()->json([
                'error' => false,
                'msg' => 'ສຳເລັດແລ້ວ'
            ], 200);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'error' => true,
                'msg' => 'ບໍ່ສາມາດລຶບໄດ້...'
            ], 422);
        }
    }

    public function updateQuotationStatus($request)
    {
        $updateStatus = Quotation::find($request['id']);
        $updateStatus->status = $request['status'];
        $updateStatus->updated_by = Auth::user('api')->id;
        $updateStatus->save();

        return response()->json([
            'errors' => false,
            'msg' => 'ສຳເລັດແລ້ວ',
        ], 200);
    }
}
