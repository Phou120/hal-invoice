<?php

namespace App\Services;

use App\Models\User;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Receipt;
use App\Models\Currency;
use App\Models\Customer;
use App\Helpers\myHelper;
use App\Models\Quotation;
use App\Traits\ResponseAPI;
use App\Helpers\TableHelper;
use App\Models\InvoiceDetail;
use App\Models\ReceiptDetail;
use App\Helpers\generateHelper;
use App\Models\QuotationDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Exists;

class InvoiceService
{
    use ResponseAPI;

    public $calculateService;

    public function __construct(CalculateService $calculateService)
    {
        $this->calculateService = $calculateService;
    }


    /** ບັນທຶກໃບບິນເກັບເງິນ */
    public function addInvoice($request)
    {
        DB::beginTransaction();

            $getTotalQuotation = $this->calculateService->sumTotalQuotation($request);
            if(!$getTotalQuotation){
                $addInvoice = new Invoice();
                $addInvoice->invoice_number = generateHelper::generateInvoiceNumber('IV- ', 8);
                $addInvoice->invoice_name = $request['invoice_name'];
                $addInvoice->currency_id = $request['currency_id'];
                $addInvoice->quotation_id = $request['quotation_id'];
                $addInvoice->customer_id = $request['customer_id'];
                $addInvoice->start_date = $request['start_date'];
                $addInvoice->discount = $request['discount'];
                $addInvoice->end_date = $request['end_date'];
                $addInvoice->note = $request['note'];
                $addInvoice->created_by = Auth::user('api')->id;
                $addInvoice->tax = myHelper::TAX;
                $addInvoice->save();

                if(!empty($request['invoice_details'])){
                    foreach($request['invoice_details'] as $item){
                        $addDetail = new InvoiceDetail();
                        $addDetail->order = $item['order'];
                        $addDetail->invoice_id = $addInvoice['id'];
                        $addDetail->name = $item['name'];
                        $addDetail->amount = $item['amount'];
                        $addDetail->price = $item['price'];
                        $addDetail->description = $item['description'];
                        $addDetail->total = $item['amount'] * $item['price'];
                        $addDetail->save();
                    }
                }

                DB::commit();

                return response()->json([
                    'error' => false,
                    'msg' => 'ສຳເລັດແລ້ວ'
                ], 200);
            }else{
                return response()->json([
                    'error' => true,
                    'msg' => 'ທ່ານບໍ່ສາມາດສ້າງໃບເກັບເງິນເກີນນີ້ໄດ້: ' . $getTotalQuotation
                ], 422);
            }
    }

    /** ດຶງໃບບິນເກັບເງິນ */
    public function listInvoices()
    {
        $listInvoice = Invoice::select(
            'invoices.*',
            DB::raw('(SELECT COUNT(*) FROM invoice_details WHERE invoice_details.invoice_id = invoices.id) as count_details'),
        )
        ->leftJoin('customers', 'invoices.customer_id', 'customers.id')
        ->leftJoin('currencies', 'invoices.currency_id', 'currencies.id')
        ->leftJoin('quotations as quotation', 'invoices.quotation_id', 'quotation.id')
        ->leftJoin('users', 'invoices.created_by', 'users.id')
        ->orderBy('invoices.id', 'desc')->get();

        $listInvoice->map(function ($item){
            /** loop data */
            TableHelper::formatDataInvoice($item);
        });

        return response()->json([
            'listInvoice' => $listInvoice
        ], 200);
    }

    /** ບັນທຶກລາຍລະອຽດໃບບິນ */
    public function addInvoiceDetail($request)
    {
        $checkBalance = $this->calculateService->checkBalanceInvoice($request);
        if(!$checkBalance){
            $addDetail = new InvoiceDetail();
            $addDetail->description = $request['description'];
            $addDetail->invoice_id = $request['id'];
            $addDetail->amount = $request['amount'];
            $addDetail->price = $request['price'];
            $addDetail->order = $request['order'];
            $addDetail->name = $request['name'];
            $addDetail->total = $request['amount'] * $request['price'];
            $addDetail->save();

            return response()->json([
                'error' => false,
                'msg' => 'ສຳເລັດແລ້ວ'
            ], 200);
        }else{
            return response()->json([
                'error' => false,
                'msg' => 'ທ່ານບໍ່ສາມາດສ້າງໃບເກັບເງິນເກີນນີ້ໄດ້: ' . $checkBalance
            ], 422);
        }
    }

    /** ດຶງລາຍລະອຽດໃບບິນ */
    public function listInvoiceDetail($request)
    {
        $item = DB::table('invoices')
        ->select(
            'invoices.*',
            DB::raw('(SELECT COUNT(*) FROM invoice_details WHERE invoice_details.invoice_id = invoices.id) as count_details')
        )
        ->leftJoin('customers', 'invoices.customer_id', 'customers.id')
        ->leftJoin('currencies', 'invoices.currency_id', 'currencies.id')
        ->leftJoin('quotations as quotation', 'invoices.quotation_id', 'quotation.id')
        ->leftJoin('users', 'invoices.created_by', 'users.id')
        ->where('invoices.id', $request->id)
        ->orderBy('id', 'desc')->first();

        /** loop data */
        TableHelper::formatDataInvoice($item);

        /**Detail */
        $details = InvoiceDetail::where('invoice_id', $request->id)->get();


        return response()->json([
            'invoice' => $item,
            'details' => $details,
        ], 200);
    }

    /** ແກ້ໄຂໃບບິນເກັບເງິນ */
    public function editInvoice($request)
    {
        $editInvoice = Invoice::find($request['id']);
        $editInvoice->invoice_name = $request['invoice_name'];
        $editInvoice->start_date = $request['start_date'];
        $editInvoice->end_date = $request['end_date'];
        $editInvoice->note = $request['note'];
        $editInvoice->updated_by = Auth::user('api')->id;
        $editInvoice->save();

        return response()->json([
            'error' => false,
            'msg' => 'ສຳເລັດແລ້ວ'
        ], 200);
    }

    /** ແກ້ໄຂລາຍລະອຽດໃບບິນ */
    public function editInvoiceDetail($request)
    {
        $checkBalance = $this->calculateService->checkBalanceInvoiceByEdit($request);
        if(!$checkBalance){
            $editDetail = InvoiceDetail::find($request['id']);
            $editDetail->order = $request['order'];
            $editDetail->name = $request['name'];
            $editDetail->amount = $request['amount'];
            $editDetail->price = $request['price'];
            $editDetail->description = $request['description'];
            $editDetail->total = $request['amount'] * $request['price'];
            $editDetail->save();

            return response()->json([
                'error' => false,
                'msg' => 'ສຳເລັດແລ້ວ'
            ], 200);
        }else{
            return response()->json([
                'error' => false,
                'msg' => 'ທ່ານບໍ່ສາມາດສ້າງໃບເກັບເງິນເກີນນີ້ໄດ້: ' . $checkBalance
            ], 422);
        }
    }

    /** ລຶບລາຍລະອຽດໃບບິນ */
    public function deleteInvoiceDetail($request)
    {
        $deleteDetail = InvoiceDetail::find($request['id']);
        $deleteDetail->delete();

        return response()->json([
            'error' => false,
            'msg' => 'ສຳເລັດແລ້ວ'
        ], 200);
    }

    /** ລຶບໃບບິນເກັບເງິນ */
    public function deleteInvoice($request)
    {
        try {

            DB::beginTransaction();

                // Find the Invoice model
                $invoice = Invoice::findOrFail($request['id']);
                $invoice->updated_by = Auth::user('api')->id;
                if($invoice['quotation_id']){
                    $getQuotation = Quotation::find($invoice['quotation_id']);
                    if($getQuotation){
                        $getQuotation->total += $invoice['total'];
                        $getQuotation->save();
                    }
                }
                $invoice->save();

                 // Delete the InvoiceDetails and the Invoice model
                $invoice->delete();
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

    /** update ສະຖານະ */
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
