<?php

namespace App\Services;

use App\Models\Quotation;
use App\Traits\ResponseAPI;
use App\Helpers\TableHelper;
use App\Helpers\filterHelper;
use App\Helpers\generateHelper;
use App\Models\QuotationDetail;
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


            /** add detail */
            $sumSubTotal = 0;
            if(!empty($request['quotation_details'])){
                foreach($request['quotation_details'] as $item){
                    $total = $item['amount'] * $getQuotationType['rate'];

                    $addDetail = new QuotationDetail();
                    $addDetail->order = $item['order'];
                    $addDetail->quotation_id = $addQuotation['id'];
                    $addDetail->name = $item['name'];
                    $addDetail->amount = $item['amount'];
                    $addDetail->price = $getQuotationType['rate'];
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
        $perPage = $request->per_page;

        $query = Quotation::select(
            'quotations.*',
            DB::raw('(SELECT COUNT(*) FROM quotation_details WHERE quotation_details.quotation_id = quotations.id) as count_details')
        );

        /** filter: start_date and end_date */
        $query = filterHelper::quotationFilter($query, $request);

        $totalQuotation = (clone $query)->count(); // count all Quotations

        $quotation = (clone $query)->orderBy('quotations.id', 'asc')->get();

        $totalPrice = $quotation->sum('total'); // sum all Quotations


        $quotationStatusCreated = (clone $query)->where('status', filterHelper::INVOICE_STATUS['CREATED'])->orderBy('quotations.id', 'asc')
        ->where('quotations.created_by', auth()->user()->id)->get();

        $created = (clone $quotationStatusCreated)->count(); // count status
        $createdTotal = (clone $quotationStatusCreated)->sum('total'); // sum total of quotation all


        $quotationStatusApproved = (clone $query)->where('status', filterHelper::INVOICE_STATUS['APPROVED'])->orderBy('quotations.id', 'asc')
        ->where('quotations.created_by', auth()->user()->id)->get();

        $approved = (clone $quotationStatusApproved)->count(); // count status
        $approvedTotal = (clone $quotationStatusApproved)->sum('total'); // sum total of quotation all


        $quotationStatusInprogress = (clone $query)->where('status', filterHelper::INVOICE_STATUS['INPROGRESS'])->orderBy('quotations.id', 'asc')
        ->where('quotations.created_by', auth()->user()->id)->get();

        $inprogress = (clone $quotationStatusInprogress)->count(); // count status
        $inprogressTotal = (clone $quotationStatusInprogress)->sum('total'); // sum total of quotation all


        $quotationStatusCompleted = (clone $query)->where('status', filterHelper::INVOICE_STATUS['COMPLETED'])->orderBy('quotations.id', 'asc')
        ->where('quotations.created_by', auth()->user()->id)->get();

        $completed = (clone $quotationStatusCompleted)->count(); // count status
        $completedTotal = (clone $quotationStatusCompleted)->sum('total'); // sum total of quotation all


        $quotationStatusCancelled = (clone $query)->where('status', filterHelper::INVOICE_STATUS['CANCELLED'])->orderBy('quotations.id', 'asc')
        ->where('quotations.created_by', auth()->user()->id)->get();

        $cancelled = (clone $quotationStatusCancelled)->count(); // count status
        $cancelledTotal = (clone $quotationStatusCancelled)->sum('total'); // sum total of quotation all


        /** query: status */
        $query = filterHelper::filterStatus($query, $request);

        /** filter DI */
        $query = filterHelper::filterID($query, $request);

        /** filter Name */
        $query = filterHelper::filterQuotationName($query, $request);
        /** filter total */
        $query = filterHelper::filterTotal($query, $request);

        $listQuotations = (clone $query)->orderBy('id', 'asc')
        ->where('quotations.created_by', auth()->user()->id)
        ->paginate($perPage);

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

    /** add quotation detail */
    public function addQuotationDetail($request)
    {
        $detailData = (new ReturnService())->detailData($request);

        $queryQuotationType = (new ReturnService())->getQuotationType($request);

        if ($queryQuotationType) {
            $rate = $queryQuotationType->rate;
            $detailData['price'] = $rate;
            $detailData['total'] = $request['amount'] * $rate;

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
        $item = DB::table('quotations')
        ->select('quotations.*',
        DB::raw('(SELECT COUNT(*) FROM quotation_details WHERE quotation_id = quotations.id) AS count_detail')
        )
        ->leftJoin('customers', 'quotations.customer_id', 'customers.id')
        ->leftJoin('currencies', 'quotations.currency_id', 'currencies.id')
        ->leftJoin('users', 'quotations.created_by', 'users.id')
        ->where('quotations.id', $request->id)
        ->orderBy('id', 'desc')->first();

        /** loop data */
        TableHelper::loopDataInQuotation($item);

        /**Detail */
        $details = QuotationDetail::where('quotation_id', $request->id)->get();


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
        $editQuotation->customer_id = $request['customer_id'];
        $editQuotation->currency_id = $request['currency_id'];
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

            // Find the Quotation model
            $quotation = Quotation::findOrFail($request['id']);
            $quotation->updated_by = Auth::user('api')->id;
            $quotation->save();

            // Delete the Quotation details and the Quotation model
            $quotation->delete();
            QuotationDetail::where('quotation_id', $request['id'])->delete();

            DB::commit();

            return response()->json([
                'error' => false,
                'msg' => 'ສຳເລັດແລ້ວ'
            ], 200);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'error' => true,
                'msg' => 'ບໍ່ສາມາດລຶບລາຍກນ້ໄດ້...'
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
