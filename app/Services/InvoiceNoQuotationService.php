<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Currency;
use App\Models\InvoiceRate;
use App\Traits\ResponseAPI;
use App\Helpers\filterHelper;
use App\Models\InvoiceDetail;
use App\Helpers\generateHelper;
use App\Services\CalculateService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Services\GetData\GetDataOfQuotationService;

class InvoiceNoQuotationService
{
    use ResponseAPI;

    public $calculateService;
    public $getDataOfQuotationService;

    public function __construct(
        CalculateService $calculateService,
        GetDataOfQuotationService $getDataOfQuotationService
    )
    {
        $this->calculateService = $calculateService;
        $this->getDataOfQuotationService = $getDataOfQuotationService;
    }

    /** add invoice ທີ່ບໍ່ມີ id quotation */
    public function addInvoiceNoQuotationID($request)
    {
        DB::beginTransaction();

            $addInvoice = new Invoice();
            $addInvoice->invoice_number = generateHelper::generateInvoiceNumber('IV- ', 8);
            $addInvoice->invoice_name = $request['invoice_name'];
            $addInvoice->customer_id = $request['customer_id'];
            $addInvoice->start_date = $request['start_date'];
            $addInvoice->end_date = $request['end_date'];
            $addInvoice->type_quotation = 0;
            $addInvoice->note = $request['note'];
            $addInvoice->created_by = Auth::user('api')->id;
            $addInvoice->save();

            $totalHours = 0;
            if(!empty($request['invoice_details'])){
                foreach($request['invoice_details'] as $item){
                    $addDetail = new InvoiceDetail();
                    $addDetail->order = $item['order'];
                    $addDetail->invoice_id = $addInvoice['id'];
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
                foreach($getCurrencies as $currency) {
                    $addInvoiceRate = new InvoiceRate();
                    $addInvoiceRate->invoice_id = $addInvoice['id'];
                    $addInvoiceRate->currency_id = $currency['id'];
                    $addInvoiceRate->rate = $currency['rate'];
                    $addInvoiceRate->sub_total = $totalHours * $currency['rate'];
                    $addInvoiceRate->discount = $request['discount'];
                    $addInvoiceRate->save();
                    // return $addInvoiceRate;
                }
                foreach ($getCurrencies as $currency) {
                    $addInvoiceRate = InvoiceRate::where('invoice_id', $addInvoice['id'])
                        ->where('currency_id', $currency->id)
                        ->first();

                    $this->calculateService->calculateTotalOnQuotationID($request, $addInvoiceRate->sub_total, $addInvoiceRate->id);
                }
            }

            /**Calculate */

        DB::commit();

        return response()->json([
            'error' => false,
            'msg' => 'ສຳເລັດແລ້ວ'
        ], 200);
    }

    /** add invoice detail ທີ່ບໍ່ມີ id quotation */
    public function addInvoiceDetailNoQuotationID($request)
    {
        $hour = $request['hour'];
        DB::beginTransaction();

            $addDetail = new InvoiceDetail();
            $addDetail->description = $request['description'];
            $addDetail->invoice_id = $request['id'];
            $addDetail->order = $request['order'];
            $addDetail->name = $request['name'];
            $addDetail->hour = $hour;
            $addDetail->save();

            /** update created_by in invoice */
            filterHelper::updateCreatedByInInvoice($addDetail);

            /**Update Invoice */
            $invoiceRates = InvoiceRate::where('invoice_id', $request['id'])->get();
            /**Update Calculate */
            $this->calculateService->calculateInvoiceRate($invoiceRates, $hour);

        DB::commit();

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
        $editInvoice->customer_id = $request['customer_id'];
        $editInvoice->save();

        $invoiceRate = InvoiceRate::where('invoice_id', $editInvoice->id)->get();

        /** calculate */
        $this->calculateService->UpdateInvoiceRates($invoiceRate, $request);

        return response()->json([
            'error' => false,
            'msg' => 'ສຳເລັດແລ້ວ'
        ], 200);
    }

    /** update invoice detail ທີ່ບໍ່ມີ id quotation */
    public function editInvoiceDetailNoQuotationID($request)
    {
        DB::beginTransaction();

        try {
            $hour = $request['hour'];
            // Update QuotationDetail
            $invoiceDetail = InvoiceDetail::find($request['id']);
            $invoiceDetail->order = $request['order'];
            $invoiceDetail->name = $request['name'];
            $invoiceDetail->hour = $hour;
            $invoiceDetail->description = $request['description'];
            $invoiceDetail->save();

            /** update quotation column updated_by */
            $this->getDataOfQuotationService->getDataInvoice($invoiceDetail);

            // Calculate and update QuotationRates
            $sumHour = InvoiceDetail::where('invoice_id', $invoiceDetail->invoice_id)->sum('hour');
            $invoiceRates = InvoiceRate::where('invoice_id', $invoiceDetail->invoice_id)->get();
            // return $invoiceRates;

            /** calculate */
            $this->calculateService->updateInvoiceDetailNoQuotationID($invoiceRates, $sumHour);

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

    // /** delete invoice detail ທີ່ບໍ່ມີ id quotation */
    // public function deleteInvoiceDetailNoQuotationID($request)
    // {
    //     $deleteDetail = InvoiceDetail::find($request['id']);
    //     $deleteDetail->delete();

    //     return response()->json([
    //         'error' => false,
    //         'msg' => 'ສຳເລັດແລ້ວ'
    //     ], 200);
    // }

    /** delete invoice ທີ່ບໍ່ມີ id quotation */
    // public function deleteInvoice($request)
    // {
    //     try {

    //         DB::beginTransaction();

    //             // Find the Invoice model
    //             $invoice = Invoice::findOrFail($request['id']);
    //             $invoice->updated_by = Auth::user('api')->id;
    //             $invoice->save();
    //             $invoice->delete();

    //              // Delete the InvoiceDetails
    //             InvoiceDetail::where('invoice_id', $request['id'])->delete();

    //         DB::commit();

    //         return response()->json([
    //             'error' => false,
    //             'msg' => 'ສຳເລັດແລ້ວ'
    //         ], 200);

    //     } catch (\Exception $e) {
    //         DB::rollback();
    //         return response()->json([
    //             'error' => true,
    //             'msg' => 'ບໍ່ສາມາດລຶບລາຍການນີ້ໄດ້...'
    //         ], 422);
    //     }
    // }

    /** update invoice ທີ່ບໍ່ມີ id quotation */
    // public function updateInvoiceStatus($request)
    // {
    //     $updateStatus = Invoice::find($request['id']);
    //     $updateStatus->status = $request['status'];
    //     $updateStatus->updated_by = Auth::user('api')->id;
    //     $updateStatus->save();

    //     return response()->json([
    //         'error' => false,
    //         'msg' => 'ສຳເລັດແລ້ວ'
    //     ], 200);
    // }
}
