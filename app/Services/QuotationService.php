<?php

namespace App\Services;

use App\Models\Currency;
use App\Models\Quotation;
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

    public function __construct(
        CalculateService  $calculateService,
        GetDataOfQuotationService $getDataOfQuotationService
    )
    {
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

        $query = Quotation::select(
            'quotations.*',
            DB::raw('(SELECT COUNT(*) FROM quotation_details WHERE quotation_details.quotation_id = quotations.id) as count_details'),
            DB::raw('(SELECT SUM(total) FROM quotation_rates WHERE quotation_rates.quotation_id = quotations.id) as total_price'),
        )
        ->leftJoin('customers', 'quotations.customer_id', '=', 'customers.id')
        ->leftJoin('users', 'quotations.created_by', '=', 'users.id')
        ->leftJoin('company_users', 'company_users.user_id', '=', 'users.id')
        ->leftJoin('companies', 'company_users.company_id', '=', 'companies.id');

        /** filter start_date and end_date */
        $query = filterHelper::quotationFilter($query, $request);

        if ($user->hasRole(['superadmin', 'admin'])) {
            $query->orderBy('quotations.id', 'asc');
        }
        if ($user->hasRole(['company-admin', 'company-user'])) {
            $query->where('quotations.created_by', $user->id);
        }

        $quotation = $query->groupBy('quotations.id')->orderBy('quotations.id', 'asc')->get();
        $totalDetail = $quotation->sum('count_details');
        $totalPrice = $quotation->sum('total_price');


        function filterQuotationStatus($query, $status) {
            return $query->where('status', filterHelper::INVOICE_STATUS[$status]);
        }

        $quotationStatusCreated = filterQuotationStatus(clone $quotation, 'CREATED');
        $created = $quotationStatusCreated->count();
        $createdTotal = $quotationStatusCreated->sum('total_price');

        $quotationStatusApproved = filterQuotationStatus(clone $quotation, 'APPROVED');
        $approved = $quotationStatusApproved->count();
        $approvedTotal = $quotationStatusApproved->sum('total_price');

        $quotationStatusInprogress = filterQuotationStatus(clone $quotation, 'INPROGRESS');
        $inprogress = $quotationStatusInprogress->count();
        $inprogressTotal = $quotationStatusInprogress->sum('total_price');

        $quotationStatusCompleted = filterQuotationStatus(clone $quotation, 'COMPLETED');
        $completed = $quotationStatusCompleted->count();
        $completedTotal = $quotationStatusCompleted->sum('total_price');

        $quotationStatusCancelled = filterQuotationStatus(clone $quotation, 'CANCELLED');
        $cancelled = $quotationStatusCancelled->count();
        $cancelledTotal = $quotationStatusCancelled->sum('total_price');

        /** query: status */
        $query = filterHelper::filterStatus($query, $request);

        /** filter DI */
        $query = filterHelper::filterID($query, $request);

        /** filter Name */
        $query = filterHelper::filterQuotationName($query, $request);

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

        if (!isset($request->per_page)) {
            $listQuotations = $listQuotations->get();
        } else {
            $listQuotations = $listQuotations->paginate($request->per_page);
        }

        $listQuotations->map(function ($item) {
            TableHelper::loopDataInQuotation($item);
        });

        /** return data */
        $responseQuotationData = (new ReturnService())->QuotationData(
            $totalDetail, $totalPrice, $created, $createdTotal,
            $approved, $approvedTotal,$inprogress, $inprogressTotal,
            $completed, $completedTotal, $cancelled,$cancelledTotal, $listQuotations
        );

        return response()->json($responseQuotationData, 200);
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

        $itemQuery = DB::table('quotations')
            ->select('quotations.*', DB::raw('(SELECT COUNT(*) FROM quotation_details WHERE quotation_id = quotations.id) AS count_detail'))
            ->leftJoin('customers', 'quotations.customer_id', 'customers.id')
            ->leftJoin('users', 'quotations.created_by', 'users.id')
            ->where('quotations.id', $request->id);

        // Check user roles and apply appropriate conditions
        if ($user->hasRole(['superadmin', 'admin'])) {
            $itemQuery->orderBy('quotations.id', 'asc');
        } elseif ($user->hasRole(['company-admin', 'company-user'])) {
            $itemQuery->where('quotations.created_by', $user->id)
                ->orderBy('quotations.id', 'asc');
        }

        $item = $itemQuery->first();

        // Detail query
        $detailsQuery = QuotationDetail::select('quotation_details.*')
            ->join('quotations', 'quotation_details.quotation_id', 'quotations.id')
            ->where('quotation_id', $request->id);

        // Check user roles and apply appropriate conditions
        if ($user->hasRole(['superadmin', 'admin'])) {
            $detailsQuery->orderBy('quotations.id', 'asc');
        } elseif ($user->hasRole(['company-admin', 'company-user'])) {
            $detailsQuery->where('quotations.created_by', $user->id)
                ->orderBy('quotations.id', 'asc');
        }

        // Get the results
        $details = $detailsQuery->get();

        return response()->json([
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
