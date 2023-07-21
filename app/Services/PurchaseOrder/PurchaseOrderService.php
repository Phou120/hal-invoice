<?php

namespace App\Services\PurchaseOrder;

use App\Models\User;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Currency;
use App\Models\Customer;
use App\Traits\ResponseAPI;
use App\Helpers\TableHelper;
use App\Models\InvoiceDetail;
use App\Models\PurchaseOrder;
use App\Models\PurchaseDetail;
use App\Services\CalculateService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PurchaseOrderService
{
    use ResponseAPI;

    public $calculateService;

    public function __construct(CalculateService $calculateService)
    {
        $this->calculateService = $calculateService;
    }

     /** ບັນທຶກໃບສັ່ງຊື້  */
    public function addPurchaseOrder($request)
    {
        $getInvoice = Invoice::find($request['invoice_id']);
        if(isset($getInvoice)){
            $getInvoiceDetails = InvoiceDetail::select(
                'invoice_details.*'
            )
            ->join('invoices as invoice', 'invoice_details.invoice_id', 'invoice.id')
            ->where('invoice_details.invoice_id', $getInvoice['id'])
            ->where('invoice.status', 'created')
            ->get();

            if(count($getInvoiceDetails) > 0) {

                DB::beginTransaction();

                    /** add order */
                    $addOrder = new PurchaseOrder();
                    $addOrder->invoice_id = $getInvoice['id'];
                    $addOrder->customer_id = $getInvoice['customer_id'];
                    $addOrder->company_id = $getInvoice['company_id'];
                    $addOrder->currency_id = $getInvoice['currency_id'];
                    $addOrder->created_by = Auth::user('api')->id;
                    $addOrder->purchase_name = $request['purchase_name'];
                    $addOrder->date = $request['date'];
                    $addOrder->note = $request['note'];
                    $addOrder->total = $getInvoice['total'];
                    $addOrder->sub_total = $getInvoice['sub_total'];
                    $addOrder->discount = $getInvoice['discount'];
                    $addOrder->tax = $getInvoice['tax'];
                    $addOrder->save();

                    /** add detail */
                    foreach($getInvoiceDetails as $detail){
                        $addDetail = new PurchaseDetail();
                        $addDetail->purchase_id = $addOrder['id'];
                        $addDetail->order = $detail['order'];
                        $addDetail->name = $detail['name'];
                        $addDetail->description = $detail['description'];
                        $addDetail->amount = $detail['amount'];
                        $addDetail->price = $detail['price'];
                        $addDetail->total = $detail['total'];
                        $addDetail->save();

                    }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'msg' => 'ສຳເລັດແລ້ວ'
                ]);
            }

            return $getInvoiceDetails;
        }

        return response()->json([
            'success' => false,
            'msg' => 'ຜິດພາດ'
        ]);
    }

     /** ດຶງໃບສັ່ງຊື້  */
    public function listPurchaseOrders()
    {
            $orders = DB::table('purchase_orders')
            ->select(
                'purchase_orders.*',
                DB::raw('(SELECT COUNT(*) FROM purchase_details WHERE purchase_details.purchase_id = purchase_orders.id) as count_details')
            )
            ->leftJoin('customers', 'purchase_orders.customer_id', 'customers.id')
            ->leftJoin('currencies', 'purchase_orders.currency_id', 'currencies.id')
            ->leftJoin('companies', 'purchase_orders.company_id', 'companies.id')
            ->leftJoin('users', 'purchase_orders.created_by', 'users.id')
            ->orderBy('purchase_orders.id', 'desc')
            ->get();

        foreach ($orders as $item) {
            /**  */
            TableHelper::format($item);
        }

        return response()->json([
            'listOrder' => $orders
        ]);

    }

     /** ບັນທຶກລາຍລະອຽດການຈັດຊື້  */
    public function addPurchaseDetail($request)
    {
        $addDetail = new PurchaseDetail();
        $addDetail->order = $request['order'];
        $addDetail->purchase_id = $request['id'];
        $addDetail->name = $request['name'];
        $addDetail->description = $request['description'];
        $addDetail->amount = $request['amount'];
        $addDetail->price = $request['price'];
        $addDetail->total = $request['amount'] * $request['price'];
        $addDetail->save();

        /**Update PurchaseOrder */
        $editOrder = PurchaseOrder::find($request['id']);

        /**Update Calculate */
        $this->calculateService->calculateTotalOrder_ByEdit($editOrder);

        return response()->json([
            'success' => true,
            'msg' => 'ສຳເລັດແລ້ວ'
        ]);
    }

     /** ດຶງລາຍລະອຽດການຈັດຊື້  */
    public function listPurchaseDetail($id)
    {
        $item = DB::table('purchase_orders')
            ->select('purchase_orders.*',
                DB::raw('(SELECT COUNT(*) FROM purchase_details WHERE purchase_details.purchase_id = purchase_orders.id) as details_count')
            )
            ->leftJoin('customers', 'purchase_orders.customer_id', 'customers.id')
            ->leftJoin('currencies', 'purchase_orders.currency_id', 'currencies.id')
            ->leftJoin('companies', 'purchase_orders.company_id', 'companies.id')
            ->leftJoin('users', 'purchase_orders.created_by', 'users.id')
            ->where('purchase_orders.id', $id)
            ->orderBy('purchase_orders.id', 'desc')
            ->groupBy('purchase_orders.id')
            ->first();

        /**  */
        TableHelper::format($item);

        /** detail */
        $details = DB::table('purchase_details')->where('purchase_id', $id)->get();

        return response()->json([
            'order' => $item,
            'details' => $details,
        ]);
    }

     /** ແກ້ໄຂໃບສັ່ງຊື້  */
    public function editPurchaseOrder($request)
    {
        $editOrder = PurchaseOrder::find($request['id']);
        $editOrder->invoice_id = $request['invoice_id'];
        $editOrder->purchase_name = $request['purchase_name'];
        $editOrder->date = $request['date'];
        $editOrder->note = $request['note'];
        $editOrder->customer_id = $request['customer_id'];
        $editOrder->company_id = $request['company_id'];
        $editOrder->currency_id = $request['currency_id'];
        $editOrder->discount = $request['discount'];
        $editOrder->tax = $request['tax'];
        $editOrder->updated_by = Auth::user('api')->id;
        $editOrder->save();

         /**Update Calculate */
         $this->calculateService->calculateTotalOrder_ByEdit($request);

         return response()->json([
             'success' => true,
             'msg' => 'ສຳເລັດແລ້ວ'
         ]);
    }

     /** ແກ້ໄຂລາຍລະອຽດການຈັດຊື້ */
    public function editPurchaseDetail($request)
    {
        $editDetail = PurchaseDetail::find($request['id']);
        $editDetail->order = $request['order'];
        $editDetail->name = $request['name'];
        $editDetail->amount = $request['amount'];
        $editDetail->price = $request['price'];
        $editDetail->description = $request['description'];
        $editDetail->total = $request['amount'] * $request['price'];
        $editDetail->save();

        /**Update PurchaseOrder */
        $editOrder = PurchaseOrder::find($editDetail['purchase_id']);

        /**Update Calculate PurchaseOrder */
        $this->calculateService->calculateTotalOrder_ByEdit($editOrder);

        return response()->json([
            'success' => true,
            'msg' => 'ສຳເລັດແລ້ວ'
        ]);
    }

     /** ລຶບລາຍລະອຽດການຈັດຊື້ */
    public function deletePurchaseDetail($request)
    {
        $deleteDetail = PurchaseDetail::find($request['id']);
        $deleteDetail->delete();

        /**Update PurchaseOrder */
        $editReceipt = PurchaseOrder::find($deleteDetail['purchase_id']);

        /**Update Calculate PurchaseOrder */
        $this->calculateService->calculateTotalOrder_ByEdit($editReceipt);

        return response()->json([
            'success' => true,
            'msg' => 'ສຳເລັດແລ້ວ'
        ]);
    }

    /** ລຶບໃບສັ່ງຊື້ */
    public function deletePurchaseOrder($request)
    {
        try {

            DB::beginTransaction();

                // Find the Receipt model
                $purchaseOrder = PurchaseOrder::findOrFail($request['id']);
                $purchaseOrder->updated_by = Auth::user('api')->id;
                $purchaseOrder->save();

                // Delete the ReceiptDetail and the Receipt model
                $purchaseOrder->delete();
                PurchaseDetail::where('purchase_id', $request['id'])->delete();

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
