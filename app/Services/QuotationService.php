<?php

namespace App\Services;

use App\Models\User;
use App\Models\Company;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Quotation;
use App\Traits\ResponseAPI;
use App\Helpers\generateHelper;
use App\Models\QuotationDetail;
use App\Services\CalculateService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

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

        DB::beginTransaction();

            /** add quotation */
            $addQuotation = new Quotation();
            $addQuotation->quotation_number = generateHelper::generateQuotationNumber('QT- ', 8);
            $addQuotation->quotation_name = $request['quotation_name'];
            $addQuotation->start_date = $request['start_date'];
            $addQuotation->end_date = $request['end_date'];
            $addQuotation->note = $request['note'];
            $addQuotation->customer_id = $request['customer_id'];
            $addQuotation->company_id = $request['company_id'];
            $addQuotation->currency_id = $request['currency_id'];
            $addQuotation->discount = $request['discount'];
            $addQuotation->tax = $request['tax'];
            $addQuotation->created_by = Auth::user('api')->id;
            $addQuotation->save();


            /** add detail */
            $sumSubTotal = 0;
            if(!empty($request['quotation_details'])){
                foreach($request['quotation_details'] as $item){
                    $addDetail = new QuotationDetail();
                    $addDetail->order = $item['order'];
                    $addDetail->quotation_id = $addQuotation['id'];
                    $addDetail->name = $item['name'];
                    $addDetail->amount = $item['amount'];
                    $addDetail->price = $item['price'];
                    $addDetail->description = $item['description'];
                    $addDetail->total = $item['amount'] * $item['price'];
                    $addDetail->save();

                    $sumSubTotal += $item['amount'] * $item['price'];
                }
            }
            /**Calculate */
            $this->calculateService->calculateTotal($request, $sumSubTotal, $addQuotation['id']);

        DB::commit();

        return response()->json([
            'success' => true,
            'msg' => 'ສຳເລັດແລ້ວ'
        ]);
    }

    /** list quotation */
    public function listQuotations()
    {
        $listQuotations = Quotation::select(
            'quotations.*'
        )
        ->orderBy('quotations.id', 'desc')->get();

        $listQuotations->map(function ($item) {
            $item['countDetail'] = QuotationDetail::where('quotation_id', $item['id'])->count();
            $item['customer'] = Customer::where('id', $item['customer_id'])->first();
            $item['company'] = Company::where('id', $item['company_id'])->first();
            $item['currency'] = Currency::where('id', $item['currency_id'])->first();
            $item['user'] = User::where('id', $item['created_by'])->first();
        });

        return response()->json([
            'listQuotations' => $listQuotations,
        ]);
    }

    /** add quotation detail */
    public function addQuotationDetail($request)
    {

        $addDetail = new QuotationDetail();
        $addDetail->order = $request['order'];
        $addDetail->quotation_id = $request['id'];
        $addDetail->name = $request['name'];
        $addDetail->amount = $request['amount'];
        $addDetail->price = $request['price'];
        $addDetail->description = $request['description'];
        $addDetail->total = $request['amount'] * $request['price'];
        $addDetail->save();

        /** Update Quotation */
        $editQuotation = Quotation::find($request['id']);

        /** Update Calculate quotation */
        $this->calculateService->calculateTotal_ByEdit($editQuotation);

        return response()->json([
            'success' => true,
            'msg' => 'ສຳເລັດແລ້ວ'
        ]);
    }

    public function listQuotationDetail($id)
    {
        $item = Quotation::select('quotations.*')
        ->orderBy('id', 'desc')->where('id', $id)->first();
        $item['countDetail'] = QuotationDetail::where('quotation_id', $item['id'])->count();
        $item['customer'] = Customer::where('id', $item['customer_id'])->first();
        $item['company'] = Company::where('id', $item['company_id'])->first();
        $item['currency'] = Currency::where('id', $item['currency_id'])->first();
        $item['user'] = User::where('id', $item['created_by'])->first();

        /**Detail */
        $details = QuotationDetail::where('quotation_id', $id)->get();


        return response()->json([
            'quotation' => $item,
            'details' => $details,
        ]);
    }

    /** edit quotation */
    public function editQuotation($request)
    {
        $editQuotation = Quotation::find($request['id']);
        $editQuotation->quotation_name = $request['quotation_name'];
        $editQuotation->start_date = $request['start_date'];
        $editQuotation->end_date = $request['end_date'];
        $editQuotation->note = $request['note'];
        $editQuotation->company_id = $request['company_id'];
        $editQuotation->customer_id = $request['customer_id'];
        $editQuotation->currency_id = $request['currency_id'];
        $editQuotation->discount = $request['discount'];
        $editQuotation->tax = $request['tax'];
        $editQuotation->created_by = Auth::user('api')->id;
        $editQuotation->save();


        /**Update Calculate */
        $this->calculateService->calculateTotal_ByEdit($request);

        return $editQuotation;
    }

    /** edit detail */
    public function editQuotationDetail($request)
    {
        $editDetail = QuotationDetail::find($request['id']);
        $editDetail->order = $request['order'];
        $editDetail->name = $request['name'];
        $editDetail->amount = $request['amount'];
        $editDetail->price = $request['price'];
        $editDetail->description = $request['description'];
        $editDetail->total = $request['amount'] * $request['price'];
        $editDetail->save();

        /**Update Quotation */
        $editQuotation = Quotation::find($editDetail['quotation_id']);

        /**Update Calculate quotation */
        $this->calculateService->calculateTotal_ByEdit($editQuotation);

        return $editDetail;
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

         return $deleteDetail;
    }

    public function deleteQuotation($request)
    {

        try {

            DB::beginTransaction();

                Quotation::findOrFail($request['id'])->delete();
                QuotationDetail::where('quotation_id', $request['id'])->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'msg' => 'ສຳເລັດແລ້ວ'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'msg' => 'ບໍ່ສາມາດລຶບລາຍການນີ້ໄດ້...'
            ]);
        }
    }
}
