<?php

namespace App\Services;

use App\Models\User;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Receipt;
use App\Models\Currency;
use App\Models\Customer;
use App\Helpers\myHelper;
use App\Traits\ResponseAPI;
use App\Helpers\TableHelper;
use App\Models\InvoiceDetail;
use App\Models\ReceiptDetail;
use App\Services\CalculateService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ReceiptService
{
    use ResponseAPI;

    public $calculateService;

    public function __construct(CalculateService $calculateService)
    {
        $this->calculateService = $calculateService;
    }

    /** ບັນທຶກໃບຮັບເງິນ */
    public function addReceipt($request)
    {
        $getInvoice = Invoice::find($request['invoice_id']);
        if(isset($getInvoice)){
            $getInvoiceDetail = InvoiceDetail::select(
                'invoice_details.*'
            )
            ->join('invoices as invoice', 'invoice_details.invoice_id', 'invoice.id')
            ->where('invoice_details.invoice_id', $getInvoice['id'])
            ->where('status', myHelper::INVOICE_STATUS['APPROVED'])
            ->get();

            if(count($getInvoiceDetail) > 0) {

                DB::beginTransaction();

                    $addReceipt = new Receipt();
                    $addReceipt->invoice_id = $getInvoice['id'];
                    $addReceipt->customer_id = $getInvoice['customer_id'];
                    $addReceipt->currency_id = $getInvoice['currency_id'];
                    $addReceipt->receipt_name = $request['receipt_name'];
                    $addReceipt->receipt_date = $request['receipt_date'];
                    $addReceipt->created_by = Auth::user('api')->id;
                    $addReceipt->discount = $getInvoice['discount'];
                    $addReceipt->tax = $getInvoice['tax'];
                    $addReceipt->note = $request['note'];
                    $addReceipt->save();

                    foreach($getInvoiceDetail as $item){
                        $addDetail = new ReceiptDetail();
                        $addDetail->receipt_id = $addReceipt['id'];
                        $addDetail->order = $item['order'];
                        $addDetail->name = $item['name'];
                        $addDetail->description = $item['description'];
                        $addDetail->amount = $item['amount'];
                        $addDetail->price = $item['price'];
                        $addDetail->total = $item['total'];
                        $addDetail->save();
                    }

                DB::commit();

                return response()->json([
                    'error' => false,
                    'msg' => 'ສຳເລັດແລ້ວ'
                ], 200);
            }

            return response()->json([
                'msg' => 'ສະຖານະຂອງໃບເກັບເງິນຄວນເປັນ approved ພວກເຮົາຈື່ງສາມາດອອກໃບຮັບເງິນໄດ້...'
            ], 422);
        }

        return response()->json([
            'error' => true,
            'msg' => 'ຜິດພາດ'
        ], 500);
    }

    /** ດຶງຂໍມູນໃບຮັບເງິນ */
    public function listReceipts($request)
    {
        $perPage = $request->per_page;

        $listReceipt = DB::table('Receipts')
        ->select(
            'receipts.*',
            DB::raw('(SELECT COUNT(*) FROM receipt_details WHERE receipt_details.receipt_id = Receipts.id) as count_details')
        )
        ->orderBy('receipts.id', 'asc')->paginate($perPage);

        /** loop data */
        foreach ($listReceipt as $item) {
            TableHelper::loopDataOfReceipt($item);
        }


        return response()->json([
            'listReceipt' => $listReceipt
        ], 200);
    }

    /** ດຶງຂໍມູນລາຍລະອຽດໃບຮັບເງິນ */
    public function listReceiptDetail($request)
    {
        $item = DB::table('receipts')
            ->select('receipts.*',
            DB::raw('(SELECT COUNT(*) FROM receipt_details WHERE receipt_details.receipt_id = Receipts.id) as count_details')
            )
            ->leftJoin('invoices', 'receipts.invoice_id', 'invoices.id')
            ->leftJoin('customers', 'receipts.customer_id', 'customers.id')
            ->leftJoin('currencies', 'receipts.currency_id', 'currencies.id')
            ->leftJoin('users', 'receipts.created_by', 'users.id')
            ->where('receipts.id', $request->id)
            ->orderBy('receipts.id', 'desc')
            ->first();

        /** loop data */
        TableHelper::loopDataOfReceipt($item);

        /**Detail */
        $details = DB::table('receipt_details')->where('receipt_id', $request->id)->get();

        return response()->json([
            'receipt' => $item,
            'details' => $details,
        ], 200);
    }

    /** ແກ້ໄຂໃບຮັບເງິນ */
    public function editReceipt($request)
    {
        $editReceipt = Receipt::find($request['id']);
        $editReceipt->receipt_name = $request['receipt_name'];
        $editReceipt->receipt_date = $request['receipt_date'];
        $editReceipt->note = $request['note'];
        $editReceipt->updated_by = Auth::user('api')->id;
        $editReceipt->save();

        return response()->json([
            'error' => false,
            'msg' => 'ສຳເລັດແລ້ວ'
        ], 200);
    }

    /** ລຶບໃບຮັບເງິນ */
    public function deleteReceipt($request)
    {
        try {

            DB::beginTransaction();

                // Find the Receipt model
                $receipt = Receipt::findOrFail($request['id']);
                $receipt->updated_by = Auth::user('api')->id;
                $receipt->save();
                $receipt->delete();

                // Delete the ReceiptDetail
                ReceiptDetail::where('receipt_id', $request['id'])->delete();

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
}
