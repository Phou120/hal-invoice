<?php

namespace App\Services;

use App\Models\User;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Receipt;
use App\Models\Currency;
use App\Models\Customer;
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
            ->where('invoice.status', 'created')
            ->get();

            if(count($getInvoiceDetail) > 0) {

                DB::beginTransaction();

                    $addReceipt = new Receipt();
                    $addReceipt->invoice_id = $getInvoice['id'];
                    $addReceipt->customer_id = $getInvoice['customer_id'];
                    $addReceipt->currency_id = $getInvoice['currency_id'];
                    $addReceipt->company_id = $getInvoice['company_id'];
                    $addReceipt->receipt_name = $request['receipt_name'];
                    $addReceipt->receipt_date = $request['receipt_date'];
                    $addReceipt->created_by = Auth::user('api')->id;
                    $addReceipt->sub_total = $getInvoice['sub_total'];
                    $addReceipt->discount = $getInvoice['discount'];
                    $addReceipt->total = $getInvoice['total'];
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
                    'success' => true,
                    'msg' => 'ສຳເລັດແລ້ວ'
                ]);
            }

            return $getInvoiceDetail;
        }

        return response()->json([
            'success' => false,
            'msg' => 'ຜິດພາດ'
        ]);
    }

    /** ດຶງຂໍມູນໃບຮັບເງິນ */
    public function listReceipts()
    {
        $listReceipt = DB::table('Receipts')
        ->select(
            'receipts.*',
            DB::raw('(SELECT COUNT(*) FROM receipt_details WHERE receipt_details.receipt_id = Receipts.id) as count_details')
        )
        ->leftJoin('customers', 'receipts.customer_id', '=', 'customers.id')
        ->leftJoin('currencies', 'receipts.currency_id', '=', 'currencies.id')
        ->leftJoin('companies', 'receipts.company_id', '=', 'companies.id')
        ->leftJoin('users', 'receipts.created_by', '=', 'users.id')
        ->orderBy('receipts.id', 'desc')->get();

        foreach ($listReceipt as $item) {
             /** loop data */
            TableHelper::format($item);
        }


        return response()->json([
            'listReceipt' => $listReceipt
        ]);
    }

    /** ບັນທຶກລາຍລະອຽດໃບຮັບເງິນ */
    public function addReceiptDetail($request)
    {
        $addDetail = new ReceiptDetail();
        $addDetail->description = $request['description'];
        $addDetail->receipt_id = $request['id'];
        $addDetail->amount = $request['amount'];
        $addDetail->price = $request['price'];
        $addDetail->order = $request['order'];
        $addDetail->name = $request['name'];
        $addDetail->total = $request['amount'] * $request['price'];
        $addDetail->save();

        /**Update Receipt */
        $editReceipt = Receipt::find($request['id']);

        /**Update Calculate */
        $this->calculateService->calculateTotalReceipt_ByEdit($editReceipt);

        return response()->json([
            'success' => true,
            'msg' => 'ສຳເລັດແລ້ວ'
        ]);
    }

    /** ດຶງຂໍມູນລາຍລະອຽດໃບຮັບເງິນ */
    public function listReceiptDetail($id)
    {
        $item = DB::table('receipts')
            ->select('receipts.*',
            DB::raw('(SELECT COUNT(*) FROM receipt_details WHERE receipt_details.receipt_id = Receipts.id) as count_details')
            )
            ->leftJoin('customers', 'receipts.customer_id', 'customers.id')
            ->leftJoin('currencies', 'receipts.currency_id', 'currencies.id')
            ->leftJoin('companies', 'receipts.company_id', 'companies.id')
            ->leftJoin('users', 'receipts.created_by', 'users.id')
            ->where('receipts.id', $id)
            ->orderBy('receipts.id', 'desc')
            ->first();

        /** loop data */
        TableHelper::format($item);

        /**Detail */
        $details = DB::table('receipt_details')->where('receipt_id', $id)->get();


        return response()->json([
            'receipt' => $item,
            'details' => $details,
        ]);
    }

    /** ແກ້ໄຂໃບຮັບເງິນ */
    public function editReceipt($request)
    {
        $editReceipt = Receipt::find($request['id']);
        $editReceipt->invoice_id = $request['invoice_id'];
        $editReceipt->receipt_name = $request['receipt_name'];
        $editReceipt->receipt_date = $request['receipt_date'];
        $editReceipt->note = $request['note'];
        $editReceipt->company_id = $request['company_id'];
        $editReceipt->currency_id = $request['currency_id'];
        $editReceipt->customer_id = $request['customer_id'];
        $editReceipt->discount = $request['discount'];
        $editReceipt->tax = $request['tax'];
        $editReceipt->updated_by = Auth::user('api')->id;
        $editReceipt->save();

        /**Update Calculate */
        $this->calculateService->calculateTotalReceipt_ByEdit($request);

        return response()->json([
            'success' => true,
            'msg' => 'ສຳເລັດແລ້ວ'
        ]);
    }

    /** ແກ້ໄຂລາຍລະອຽດໃບຮັບເງິນ */
    public function editReceiptDetail($request)
    {
        $editDetail = ReceiptDetail::find($request['id']);
        $editDetail->name = $request['name'];
        $editDetail->order = $request['order'];
        $editDetail->price = $request['price'];
        $editDetail->amount = $request['amount'];
        $editDetail->description = $request['description'];
        $editDetail->total = $request['amount'] * $request['price'];
        $editDetail->save();

        /**Update Receipt */
        $editReceipt = Receipt::find($editDetail['receipt_id']);

        /**Update Calculate Receipt */
        $this->calculateService->calculateTotalReceipt_ByEdit($editReceipt);

        return response()->json([
            'success' => true,
            'msg' => 'ສຳເລັດແລ້ວ'
        ]);
    }

    /** ລຶບລາຍລະອຽດໃບຮັບເງິນ */
    public function deleteReceiptDetail($request)
    {
        $deleteDetail = ReceiptDetail::find($request['id']);
        $deleteDetail->delete();

        /**Update Receipt */
        $editReceipt = Receipt::find($deleteDetail['receipt_id']);

        /**Update Calculate Receipt */
        $this->calculateService->calculateTotalReceipt_ByEdit($editReceipt);

        return response()->json([
            'success' => true,
            'msg' => 'ສຳເລັດແລ້ວ'
        ]);
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

                // Delete the ReceiptDetail and the Receipt model
                $receipt->delete();
                ReceiptDetail::where('receipt_id', $request['id'])->delete();

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
