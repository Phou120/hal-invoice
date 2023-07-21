<?php

namespace App\Services;

use App\Models\User;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Currency;
use App\Models\Customer;
use App\Traits\ResponseAPI;
use App\Models\InvoiceDetail;
use App\Helpers\generateHelper;
use App\Models\QuotationDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

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

            $addInvoice = new Invoice();
            $addInvoice->invoice_number = generateHelper::generateInvoiceNumber('IV- ', 8);
            $addInvoice->invoice_name = $request['invoice_name'];
            $addInvoice->currency_id = $request['currency_id'];
            $addInvoice->customer_id = $request['customer_id'];
            $addInvoice->company_id = $request['company_id'];
            $addInvoice->start_date = $request['start_date'];
            $addInvoice->discount = $request['discount'];
            $addInvoice->end_date = $request['end_date'];
            $addInvoice->note = $request['note'];
            $addInvoice->tax = $request['tax'];
            $addInvoice->created_by = Auth::user('api')->id;
            $addInvoice->save();


            $sumSubTotal = 0;
            if(!empty($request['invoice_details'])){
                foreach($request['invoice_details'] as $item){
                    $total = $item['amount'] * $item['price'];

                    $addDetail = new InvoiceDetail();
                    $addDetail->order = $item['order'];
                    $addDetail->invoice_id = $addInvoice['id'];
                    $addDetail->name = $item['name'];
                    $addDetail->amount = $item['amount'];
                    $addDetail->price = $item['price'];
                    $addDetail->description = $item['description'];
                    $addDetail->total = $total;
                    $addDetail->save();

                    $sumSubTotal += $total;
                }
            }

            /**Calculate */
            $this->calculateService->calculateTotalInvoice($request, $sumSubTotal, $addInvoice['id']);

        DB::commit();

        return response()->json([
            'success' => true,
            'msg' => 'ສຳເລັດແລ້ວ'
        ]);
    }

    /** ດຶງໃບບິນເກັບເງິນ */
    public function listInvoices()
    {
        $listInvoice = Invoice::select(
            'invoices.*'
        )->orderBy('invoices.id', 'desc')->get();

        $listInvoice->map(function ($item){
            $item['countDetail'] = InvoiceDetail::where('invoice_id', $item['id'])->count();
            $item['customer'] = Customer::where('id', $item['customer_id'])->first();
            $item['company'] = Company::where('id', $item['company_id'])->first();
            $item['currency'] = Currency::where('id', $item['currency_id'])->first();
            $item['user'] = User::where('id', $item['created_by'])->first();
        });

        return response()->json([
            'listInvoice' => $listInvoice,
        ]);
    }

    /** ບັນທຶກລາຍລະອຽດໃບບິນ */
    public function addInvoiceDetail($request)
    {
        $addDetail = new InvoiceDetail();
        $addDetail->description = $request['description'];
        $addDetail->invoice_id = $request['id'];
        $addDetail->amount = $request['amount'];
        $addDetail->price = $request['price'];
        $addDetail->order = $request['order'];
        $addDetail->name = $request['name'];
        $addDetail->total = $request['amount'] * $request['price'];
        $addDetail->save();


        /** Update Invoice */
        $editInvoice = Invoice::find($request['id']);

        /** Update Calculate Invoice */
        $this->calculateService->calculateTotalInvoice_ByEdit($editInvoice);

        return response()->json([
            'success' => true,
            'msg' => 'ສຳເລັດແລ້ວ'
        ]);
    }

    /** ດຶງລາຍລະອຽດໃບບິນ */
    public function listInvoiceDetail($id)
    {
        $item = Invoice::select('invoices.*')->orderBy('id', 'desc')->where('id', $id)->first();
        $item['countDetail'] = InvoiceDetail::where('invoice_id', $item['id'])->count();
        $item['customer'] = Customer::where('id', $item['customer_id'])->first();
        $item['currency'] = Currency::where('id', $item['currency_id'])->first();
        $item['company'] = Company::where('id', $item['company_id'])->first();
        $item['user'] = User::where('id', $item['created_by'])->first();

        /**Detail */
        $details = InvoiceDetail::where('invoice_id', $id)->get();


        return response()->json([
            'invoice' => $item,
            'details' => $details,
        ]);
    }

    /** ແກ້ໄຂໃບບິນເກັບເງິນ */
    public function editInvoice($request)
    {

        $editInvoice = Invoice::find($request['id']);
        $editInvoice->invoice_name = $request['invoice_name'];
        $editInvoice->currency_id = $request['currency_id'];
        $editInvoice->customer_id = $request['customer_id'];
        $editInvoice->company_id = $request['company_id'];
        $editInvoice->start_date = $request['start_date'];
        $editInvoice->discount = $request['discount'];
        $editInvoice->end_date = $request['end_date'];
        $editInvoice->note = $request['note'];
        $editInvoice->tax = $request['tax'];
        $editInvoice->created_by = Auth::user('api')->id;
        $editInvoice->save();

        /**Update Calculate */
        $this->calculateService->calculateTotalInvoice_ByEdit($editInvoice);

        return $editInvoice;
    }

    /** ແກ້ໄຂລາຍລະອຽດໃບບິນ */
    public function editInvoiceDetail($request)
    {
        $editDetail = InvoiceDetail::find($request['id']);
        $editDetail->order = $request['order'];
        $editDetail->name = $request['name'];
        $editDetail->amount = $request['amount'];
        $editDetail->price = $request['price'];
        $editDetail->description = $request['description'];
        $editDetail->total = $request['amount'] * $request['price'];
        $editDetail->save();

        /**Update Invoice */
        $editInvoice = Invoice::find($editDetail['invoice_id']);

        /**Update Calculate quotation */
        $this->calculateService->calculateTotalInvoice_ByEdit($editInvoice);

        return response()->json([
            'success' => true,
            'msg' => 'ສຳເລັດແລ້ວ'
        ]);
    }

    /** ລຶບລາຍລະອຽດໃບບິນ */
    public function deleteInvoiceDetail($request)
    {
        $deleteDetail = InvoiceDetail::find($request['id']);
        $deleteDetail->delete();

        /**Update Quotation */
        $editInvoice = Invoice::find($deleteDetail['invoice_id']);

        /**Update Calculate */
        $this->calculateService->calculateTotalInvoice_ByEdit($editInvoice);

        return $deleteDetail;
    }

    /** ລຶບໃບບິນເກັບເງິນ */
    public function deleteInvoice($request)
    {
        try {

            DB::beginTransaction();

                Invoice::findOrFail($request['id'])->delete();
                InvoiceDetail::where('invoice_id', $request['id'])->delete();

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

    /** update ສະຖານະ */
    public function updateInvoiceStatus($request)
    {
        $updateStatus = Invoice::find($request['id']);
        $updateStatus->status = $request['status'];
        $updateStatus->save();

        return $updateStatus;
    }
}
