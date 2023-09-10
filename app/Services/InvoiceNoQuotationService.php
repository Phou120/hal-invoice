<?php

namespace App\Services;

use App\Models\Invoice;
use App\Helpers\filterHelper;
use App\Traits\ResponseAPI;
use App\Models\InvoiceDetail;
use App\Helpers\generateHelper;
use App\Services\CalculateService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class InvoiceNoQuotationService
{
    use ResponseAPI;

    public $calculateService;

    public function __construct(CalculateService $calculateService)
    {
        $this->calculateService = $calculateService;
    }

    /** add invoice ທີ່ບໍ່ມີ id quotation */
    public function addInvoiceNoQuotationID($request)
    {
        DB::beginTransaction();

            $addInvoice = new Invoice();
            $addInvoice->invoice_number = generateHelper::generateInvoiceNumber('IV- ', 8);
            $addInvoice->invoice_name = $request['invoice_name'];
            $addInvoice->currency_id = $request['currency_id'];
            // $addInvoice->quotation_id = $request['quotation_id'];
            $addInvoice->customer_id = $request['customer_id'];
            $addInvoice->start_date = $request['start_date'];
            $addInvoice->discount = $request['discount'];
            $addInvoice->end_date = $request['end_date'];
            $addInvoice->note = $request['note'];
            $addInvoice->created_by = Auth::user('api')->id;
            $addInvoice->tax = filterHelper::TAX;
            $addInvoice->save();

            $sumSubTotal = 0;
            if(!empty($request['invoice_details'])){
                foreach($request['invoice_details'] as $item){
                    $total =  $item['hour'] * $item['rate'];

                    $addDetail = new InvoiceDetail();
                    $addDetail->order = $item['order'];
                    $addDetail->invoice_id = $addInvoice['id'];
                    $addDetail->name = $item['name'];
                    $addDetail->hour = $item['hour'];
                    $addDetail->rate = $item['rate'];
                    $addDetail->description = $item['description'];
                    $addDetail->total = $total;
                    $addDetail->save();

                    $sumSubTotal += $total;
                }
            }

            /**Calculate */
            $this->calculateService->calculateInvoiceNoQuotation($request, $sumSubTotal, $addInvoice['id']);

        DB::commit();

        return response()->json([
            'error' => false,
            'msg' => 'ສຳເລັດແລ້ວ'
        ], 200);
    }

    /** add invoice detail ທີ່ບໍ່ມີ id quotation */
    public function addInvoiceDetailNoQuotationID($request)
    {
        $addDetail = new InvoiceDetail();
        $addDetail->description = $request['description'];
        $addDetail->invoice_id = $request['id'];
        $addDetail->hour = $request['hour'];
        $addDetail->rate = $request['rate'];
        $addDetail->order = $request['order'];
        $addDetail->name = $request['name'];
        $addDetail->total = $request['hour'] * $request['rate'];
        $addDetail->save();

        /**Update Invoice */
        $editInvoice = Invoice::find($request['id']);

        /**Update Calculate */
        $this->calculateService->calculateTotalInvoice_ByEdit($editInvoice);

        return response()->json([
            'error' => false,
            'msg' => 'ສຳເລັດແລ້ວ'
        ], 200);
    }

    /** update invoice ທີ່ບໍ່ມີ id quotation */
    public function editInvoiceNoQuotationID($request)
    {
        $editInvoice = Invoice::find($request['id']);
        $editInvoice->invoice_name = $request['invoice_name'];
        $editInvoice->start_date = $request['start_date'];
        $editInvoice->end_date = $request['end_date'];
        $editInvoice->note = $request['note'];
        $editInvoice->discount = $request['discount'];
        $editInvoice->customer_id = $request['customer_id'];
        $editInvoice->currency_id = $request['currency_id'];
        $editInvoice->updated_by = Auth::user('api')->id;
        $editInvoice->save();

        return response()->json([
            'error' => false,
            'msg' => 'ສຳເລັດແລ້ວ'
        ], 200);
    }

    /** update invoice detail ທີ່ບໍ່ມີ id quotation */
    public function editInvoiceDetailNoQuotationID($request)
    {
        $editDetail = InvoiceDetail::find($request['id']);
        $editDetail->order = $request['order'];
        $editDetail->name = $request['name'];
        $editDetail->hour = $request['hour'];
        $editDetail->rate = $request['rate'];
        $editDetail->description = $request['description'];
        $editDetail->total = $request['hour'] * $request['rate'];
        $editDetail->save();

        /**Update Invoice */
        $editInvoice = Invoice::find($editDetail['invoice_id']);

        /**Update Calculate */
        $this->calculateService->calculateTotalInvoice_ByEdit($editInvoice);


        return response()->json([
            'error' => false,
            'msg' => 'ສຳເລັດແລ້ວ'
        ], 200);
    }

    /** delete invoice detail ທີ່ບໍ່ມີ id quotation */
    public function deleteInvoiceDetailNoQuotationID($request)
    {
        $deleteDetail = InvoiceDetail::find($request['id']);
        $deleteDetail->delete();

        return response()->json([
            'error' => false,
            'msg' => 'ສຳເລັດແລ້ວ'
        ], 200);
    }

    /** delete invoice ທີ່ບໍ່ມີ id quotation */
    public function deleteInvoice($request)
    {
        try {

            DB::beginTransaction();

                // Find the Invoice model
                $invoice = Invoice::findOrFail($request['id']);
                $invoice->updated_by = Auth::user('api')->id;
                $invoice->save();
                $invoice->delete();

                 // Delete the InvoiceDetails
                InvoiceDetail::where('invoice_id', $request['id'])->delete();

            DB::commit();

            return response()->json([
                'error' => false,
                'msg' => 'ສຳເລັດແລ້ວ'
            ], 200);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'error' => true,
                'msg' => 'ບໍ່ສາມາດລຶບລາຍການນີ້ໄດ້...'
            ], 422);
        }
    }

    /** update invoice ທີ່ບໍ່ມີ id quotation */
    public function updateInvoiceStatus($request)
    {
        $updateStatus = Invoice::find($request['id']);
        $updateStatus->status = $request['status'];
        $updateStatus->updated_by = Auth::user('api')->id;
        $updateStatus->save();

        return response()->json([
            'error' => false,
            'msg' => 'ສຳເລັດແລ້ວ'
        ], 200);
    }
}
