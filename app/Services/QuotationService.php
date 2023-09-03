<?php

namespace App\Services;

use App\Models\Quotation;
use App\Traits\ResponseAPI;
use App\Helpers\TableHelper;
use App\Helpers\filterHelper;
use App\Helpers\generateHelper;
use App\Models\QuotationDetail;
use App\Models\QuotationRate;
use App\Models\QuotationType;
use App\Services\CalculateService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Services\returnData\ReturnService;

class QuotationService
{
    use ResponseAPI;

    public $calculateService;

    public function __construct(CalculateService $calculateService)
    {
        $this->calculateService = $calculateService;
    }


    /** add quotation */
    public function addQuotation($request)
    {
        $getQuotationType = QuotationType::find($request['quotation_type_id']);
        if(isset($getQuotationType)){
            $getQuotationType->select('quotation_types.*')
            ->where('quotation_types', 'rate');
        }

        DB::beginTransaction();
        /** add quotation */
        $addQuotation = new Quotation();
        $addQuotation->quotation_number = generateHelper::generateQuotationNumber('QT- ', 8);
        $addQuotation->quotation_name = $request['quotation_name'];
        $addQuotation->start_date = $request['start_date'];
        $addQuotation->end_date = $request['end_date'];
        $addQuotation->note = $request['note'];
        $addQuotation->customer_id = $request['customer_id'];
        $addQuotation->quotation_type_id = $getQuotationType['id'];
        $addQuotation->currency_id = $getQuotationType['currency_id'];
        $addQuotation->created_by = Auth::user('api')->id;
        $addQuotation->discount = $request['discount'];
        $addQuotation->save();

        /** create quotation_rate */
        if(isset($addQuotation)){
            $addQuotationRate = new QuotationRate();
            $addQuotationRate->quotation_id = $addQuotation['id'];
            $addQuotationRate->rate_kip = $request['rate_kip'];
            $addQuotationRate->rate_dollar = $request['rate_dollar'];
            $addQuotationRate->rate_baht = $request['rate_baht'];
            $addQuotationRate->save();
        }

            /** add detail */
            $sumSubTotal = 0;
            if(!empty($request['quotation_details'])){
                foreach($request['quotation_details'] as $item){
                    $total = $item['hour'] * $getQuotationType['rate'];

                    $addDetail = new QuotationDetail();
                    $addDetail->order = $item['order'];
                    $addDetail->quotation_id = $addQuotation['id'];
                    $addDetail->name = $item['name'];
                    $addDetail->hour = $item['hour'];
                    $addDetail->rate = $getQuotationType['rate'];
                    $addDetail->description = $item['description'];
                    $addDetail->total = $total;
                    $addDetail->save();

                    $sumSubTotal += $total;
                    // dd($sumSubTotal);
                }
            }
            /**Calculate */
            $this->calculateService->calculateTotal($request, $sumSubTotal, $addQuotation['id']);

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
        )
        ->leftJoin('customers', 'quotations.customer_id', '=', 'customers.id')
        ->leftJoin('currencies', 'quotations.currency_id', '=', 'currencies.id')
        ->leftJoin('users', 'quotations.created_by', '=', 'users.id')
        ->leftJoin('company_users', 'company_users.user_id', 'users.id')
        ->leftJoin('companies', 'company_users.company_id', 'companies.id');

        /** filter start_date and end_date */
        $query = filterHelper::quotationFilter($query, $request);

        if ($user->hasRole(['superadmin', 'admin'])) {
            $query->orderBy('quotations.id', 'asc');
        }
        if ($user->hasRole(['company-admin', 'company-user'])) {
            $query->where('quotations.created_by', $user->id);
        }

        $quotation = $query->orderBy('quotations.id', 'asc')->get();
        $totalQuotation = $quotation->count();
        $totalPrice = $quotation->sum('total');

        function filterQuotationStatus($query, $status) {
            return $query->where('status', filterHelper::INVOICE_STATUS[$status]);
        }

        $quotationStatusCreated = filterQuotationStatus(clone $query, 'CREATED');
        $created = $quotationStatusCreated->count();
        $createdTotal = $quotationStatusCreated->sum('total');

        $quotationStatusApproved = filterQuotationStatus(clone $query, 'APPROVED');
        $approved = $quotationStatusApproved->count();
        $approvedTotal = $quotationStatusApproved->sum('total');

        $quotationStatusInprogress = filterQuotationStatus(clone $query, 'INPROGRESS');
        $inprogress = $quotationStatusInprogress->count();
        $inprogressTotal = $quotationStatusInprogress->sum('total');

        $quotationStatusCompleted = filterQuotationStatus(clone $query, 'COMPLETED');
        $completed = $quotationStatusCompleted->count();
        $completedTotal = $quotationStatusCompleted->sum('total');

        $quotationStatusCancelled = filterQuotationStatus(clone $query, 'CANCELLED');
        $cancelled = $quotationStatusCancelled->count();
        $cancelledTotal = $quotationStatusCancelled->sum('total');

        /** query: status */
        $query = filterHelper::filterStatus($query, $request);

        /** filter DI */
        $query = filterHelper::filterID($query, $request);

        /** filter Name */
        $query = filterHelper::filterQuotationName($query, $request);
        /** filter total */
        $query = filterHelper::filterTotal($query, $request);

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
            $totalQuotation, $totalPrice, $created, $createdTotal,
            $approved, $approvedTotal,$inprogress, $inprogressTotal,
            $completed, $completedTotal, $cancelled,$cancelledTotal, $listQuotations
        );

        return response()->json($responseQuotationData, 200);
    }

    public function listQuotation($request)
    {
        // $user = Auth::user();

        $query = Quotation::select(
            'quotations.*', 'companies.company_name as company_name'
            // DB::raw('(SELECT COUNT(*) FROM quotation_details WHERE quotation_details.quotation_id = quotations.id) as count_details'),
        )
        ->leftJoin('customers', 'quotations.customer_id', '=', 'customers.id')
        ->leftJoin('currencies', 'quotations.currency_id', '=', 'currencies.id')
        ->leftJoin('users', 'quotations.created_by', '=', 'users.id')
        ->leftJoin('company_users', 'company_users.user_id', 'users.id')
        ->leftJoin('companies', 'company_users.company_id', 'companies.id')
        ->orderBy('id', 'asc')->get();

        return response()->json(['listQuotations' => $query,], 200);
    }

    /** add quotation detail */
    public function addQuotationDetail($request)
    {
        $detailData = (new ReturnService())->detailData($request);

        $queryQuotationType = (new ReturnService())->getQuotationType($request);

        if ($queryQuotationType) {
            $rate = $queryQuotationType->rate;
            $detailData['price'] = $rate;
            $detailData['total'] = $request['hour'] * $rate;

            DB::table('quotation_details')->insert($detailData);
        }

        /** Update Quotation */
        $quotation = Quotation::find($request['id']);

        /** Update Calculate quotation */
        $this->calculateService->calculateTotal_ByEdit($quotation);

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
            ->leftJoin('currencies', 'quotations.currency_id', 'currencies.id')
            ->leftJoin('users', 'quotations.created_by', 'users.id');

            if ($user->hasRole(['superadmin', 'admin'])) {
                $itemQuery->where('quotations.id', $request->id)
                    ->orderBy('quotations.id', 'asc');
            }

            if ($user->hasRole(['company-admin', 'company-user'])) {
                $itemQuery->where('quotations.id', $request->id)
                    ->where('quotations.created_by', $user->id)
                    ->orderBy('quotations.id', 'asc');
            }

            $item = $itemQuery->first();

        /**Detail */
        $details = QuotationDetail::select('quotation_details.*')
        ->join('quotations', 'quotation_details.quotation_id', 'quotations.id')
        ->where('quotation_id', $request->id);

        // Check user roles and apply appropriate conditions
        if ($user->hasRole(['superadmin', 'admin'])) {
            $details->orderBy('quotations.id', 'asc');
        } elseif ($user->hasRole(['company-admin', 'company-user'])) {
            $details->where('quotations.created_by', $user->id)
                ->orderBy('quotations.id', 'asc');
        }

        // Get the results
        $details = $details->get();

        return response()->json([
            'quotation' => $item,
            'details' => $details,
        ], 200);
    }

    /** edit quotation */
    public function editQuotation($request)
    {
        $editQuotation = Quotation::find($request['id']);
        $editQuotation->quotation_name = $request['quotation_name'];
        $editQuotation->start_date = $request['start_date'];
        $editQuotation->end_date = $request['end_date'];
        $editQuotation->note = $request['note'];
        $editQuotation->quotation_type_id = $request['quotation_type_id'];
        // $editQuotation->customer_id = $request['customer_id'];
        // $editQuotation->currency_id = $request['currency_id'];
        $editQuotation->discount = $request['discount'];
        $editQuotation->updated_by = Auth::user('api')->id;
        $editQuotation->save();

        /**Update Calculate */
        $this->calculateService->calculateTotal_ByEdit($editQuotation);

        return response()->json([
            'error' => false,
            'msg' => 'ສຳເລັດແລ້ວ'
        ], 200);
    }

    /** edit detail */
    public function editQuotationDetail($request)
    {
        /** join data */
        $editDetail = (new ReturnService())->joinData($request);

        /** update data in quotation_details */
        $updateData = (new ReturnService())->updateData($editDetail, $request);

        // Update the record
        DB::table('quotation_details')->where('id', $editDetail->id)->update($updateData);

        // Fetch the Quotation using the quotation_id from the joined data
        $editQuotation = Quotation::find($editDetail->quotation_id);

        // Update Calculate quotation
        $this->calculateService->calculateTotal_ByEdit($editQuotation);

        return response()->json([
            'error' => false,
            'msg' => 'ສຳເລັດແລ້ວ'
        ], 200);
    }

    /** delete detail */
    public function deleteQuotationDetail($request)
    {
        $deleteDetail = QuotationDetail::find($request['id']);
        $deleteDetail->delete();

         /**Update Quotation */
        $editQuotation = Quotation::find($deleteDetail['quotation_id']);

         /**Update Calculate */
         $this->calculateService->calculateTotal_ByEdit($editQuotation);

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

    public function updateDetailStatus($request)
    {
        $updateDetailStatus = QuotationDetail::find($request['id']);
        $updateDetailStatus->status_create_invoice = $request['status_create_invoice'];
        $updateDetailStatus->save();

        $updateQuotation = Quotation::find($updateDetailStatus['quotation_id']);
        $updateQuotation->updated_by = Auth::user('api')->id;
        $updateQuotation->save();

        return response()->json([
            'errors' => false,
            'msg' => 'ສຳເລັດແລ້ວ',
        ], 200);
    }
}
