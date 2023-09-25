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


            /**Calculate */
            // $this->calculateService->calculateTotal($request, $sumSubTotal, $addQuotation['id']);

        DB::commit();

        return response()->json([
            'error' => false,
            'msg' => 'ສຳເລັດແລ້ວ'
        ], 200);
    }

    /** list quotation */
    public function listQuotations($request)
    {
        $user = Auth::user();

        $query = Quotation::select('quotations.*');

        /** filter start_date and end_date */
        $query = filterHelper::quotationFilter($query, $request);

        /** check role */
        $this->returnService->checkRole($query, $user);

        $quotation = $query->groupBy('quotations.id')->orderBy('quotations.id', 'asc')->get();

        $currencyTotals = [];

        // Calculate the total count of details (if needed)
        $totalDetail = 0;

        $quotation->map(function ($item) use (&$currencyTotals, &$totalDetail) {
            // Calculate the count of details associated with the quotation
            $item->countDetail = QuotationDetail::where('quotation_id', $item->id)->get()->count();
            // dd($totalDetail);
            $totalDetail += $item->countDetail;

            // $countCompany = User::where('id', $item->created_by)->count();
            // dd($countCompany);

            // Retrieve the rates associated with the quotation
            $quotationRates = QuotationRate::where('quotation_id', $item->id)->get();

            // Iterate over the rates and accumulate currency totals
            $quotationRates->map(function ($rate) use (&$currencyTotals) {
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
                }
            });
        });

        // Sort the currencyTotals by rate in descending order and limit to the top three
        $rateCurrencies = collect($currencyTotals)->sortByDesc('rate');

        // $statuses = ["created", "approved", "inprogress", "completed", "cancelled"];
        $statuses = filterHelper::INVOICE_STATUS;

        // Initialize an empty array to store the results
        $statusTotals = [];

        // Loop through each status
        foreach ($statuses as $status) {
            // Filter quotations by status
            $filteredQuotations = Quotation::where('status', $status);

            /** check role */
            $this->returnService->checkRole($filteredQuotations, $user);

            $quotationStat = $filteredQuotations->get();
            // Initialize an array for the rates for the current status
            $statusRates = [];
            $countDetail = 0;

            // Loop through each quotation for the current status
            foreach ($quotationStat as $quotation) {
                $detailCounts = QuotationDetail::where('quotation_id', $quotation->id)->count();
                $countDetail += $detailCounts;

                // Retrieve quotation rates for the current quotation
                $quotationRates = QuotationRate::where('quotation_id', $quotation->id)->get();

                foreach ($quotationRates as $rate) {
                    $currencyId = $rate->currency_id;

                    // Check if the currency exists
                    $currency = Currency::find($currencyId);
                    if ($currency) {
                        $currencyName = $currency->short_name;

                        // Initialize the currency entry if it doesn't exist
                        if (!isset($statusRates[$currencyName])) {
                            $statusRates[$currencyName] = [
                                'currency' => $currencyName,
                                'rate' => 0,
                                'total' => 0,
                            ];
                        }

                        // Update the currency entry with rate and total values
                        $statusRates[$currencyName]['rate'] += $rate->rate;
                        $statusRates[$currencyName]['total'] += $rate->total;
                    }
                }
            }

            // Create an array for the current status with totalDetail and rates
            $statusTotals[$status] = [
                'totalDetail' => $countDetail,
                'rates' => array_values($statusRates), // Convert associative array to indexed array
            ];
        }

        /** query: status */
        $query = filterHelper::filterStatus($query, $request);

        /** filter DI */
        $query = filterHelper::filterID($query, $request);

        /** filter Name */
        $query = filterHelper::filterQuotationName($query, $request);

        /** check role superadmin and admin */
        if ($user->hasRole(['superadmin', 'admin'])) {
            $getToRole = $query->orderBy('quotations.id', 'asc');

            // return $countUserCompany;
            $countUserCompany = $this->returnService->countUserCompany($query);

            /** get to paginate */
            $role = $getToRole->paginate($request->per_page);

            /** loop data */
            $mapData = $this->returnService->mapDataInQuotation($role);

            /** format data */
            $responseData = $this->returnService->quotationDataRole(
                $totalDetail, $statusTotals, $rateCurrencies, $mapData, $countUserCompany
            );

            return response()->json($responseData, 200);
        }

        /** check role company-admin and company-user */
        if ($user->hasRole(['company-admin', 'company-user'])) {
            $getToRole = $query
                ->where(function ($query) use ($user) {
                    $query->where('quotations.created_by', $user->id);
                })
                ->orderBy('quotations.id', 'asc');

                /** get to paginate */
            $role = $getToRole->paginate($request->per_page);

            $responseData = $this->returnService->quotationData(
                $totalDetail, $statusTotals, $rateCurrencies, $getToRole
            );

            return response()->json($responseData, 200);
        }
    }

    public function listQuotation($id)
    {
        $quotation = Quotation::select([
            'quotations.*',
            DB::raw('(SELECT COUNT(*) FROM quotation_details WHERE quotation_details.quotation_id = quotations.id) as count_details')
        ])->where('id', $id)
        ->orderBy('id', 'desc')
        ->first();

        return $quotation->format();
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
            // ->select('quotations.*', DB::raw('(SELECT COUNT(*) FROM quotation_details WHERE quotation_id = quotations.id) AS count_detail'))
            // ->leftJoin('customers', 'quotations.customer_id', 'customers.id')
            // ->leftJoin('users', 'quotations.created_by', 'users.id')
            // ->where('quotations.id', $request->id);

        // Check user roles and apply appropriate conditions
        $this->returnService->checkRole($itemQuery, $user);

        // if ($user->hasRole(['superadmin', 'admin'])) {
        //     $itemQuery->orderBy('quotations.id', 'asc');
        // } elseif ($user->hasRole(['company-admin', 'company-user'])) {
        //     $itemQuery->where('quotations.created_by', $user->id)
        //         ->orderBy('quotations.id', 'asc');
        // }

        $item = $itemQuery->orderBy('quotations.id', 'asc')->get();

        // Get the count of quotation details
        $countDetail = $this->returnService->countDetail($request);
        // Get quotation rates for the given quotation
        $quotationRates = $this->returnService->quotationRate($request);
        // Use collections to group by currency and calculate sums
        $currencyTotals = $this->returnService->currencyTotals($quotationRates);
        // Sort the resulting collection by rate in descending order
        $rateCurrencies = $currencyTotals->sortByDesc('rate')->values()->toArray();

        // Detail query
        $detailsQuery = $this->returnService->detailsQuery($request);
        // Check user roles and apply appropriate conditions
        $this->returnService->checkRole($detailsQuery, $user);

        // Get the results
        $details = $detailsQuery->get();

        return response()->json([
            'countDetail' => $countDetail,
            'rate' => $rateCurrencies,
            'quotation' => $item,
            'details' => $details,
        ], 200);
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
