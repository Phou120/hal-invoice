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
use App\Helpers\filterHelper;
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
                $addInvoice->tax = filterHelper::TAX;
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
    public function listInvoices($request)
    {
        $perPage = $request->per_page;

        $query = Invoice::select(
            'invoices.*',
            DB::raw('(SELECT COUNT(*) FROM invoice_details WHERE invoice_details.invoice_id = invoices.id) as count_details'),
        );

        /** filter start_date and end_date */
        $query = filterHelper::invoiceFilter($query, $request);

        $totalBill = (clone $query)->count(); // count all invoices

        $invoice = (clone $query)->orderBy('invoices.id', 'asc')->get();

        $invoice = filterHelper::getInvoicesStatus($invoice);

        $totalPrice = $invoice->sum('total'); // sum total of invoices all

        /** where status = created */
        $invoiceStatus = (clone $query)->where('status', filterHelper::INVOICE_STATUS['CREATED'])->orderBy('invoices.id', 'asc')->get();

        $invoiceStatus = filterHelper::getInvoicesStatus($invoiceStatus); // Apply transformation

        $created = (clone $invoiceStatus)->count(); // count status
        $createdTotal = (clone $invoiceStatus)->sum('total'); // sum total of invoices all

        /** where status = approved */
        $invoiceStatusApproved = (clone $query)->where('status', filterHelper::INVOICE_STATUS['APPROVED'])->orderBy('invoices.id', 'asc')->get();

        $invoiceStatusApproved = filterHelper::getInvoicesStatus($invoiceStatusApproved); // Apply transformation

        $approved = (clone $invoiceStatusApproved)->count(); // count status
        $approvedTotal = (clone $invoiceStatusApproved)->sum('total'); // sum total of invoices all

        /** where status = inprogress */
        $invoiceStatusInprogress = (clone $query)->where('status', filterHelper::INVOICE_STATUS['INPROGRESS'])->orderBy('invoices.id', 'asc')->get();

        $invoiceStatusInprogress = filterHelper::getInvoicesStatus($invoiceStatusInprogress); // Apply transformation

        $inprogress = (clone $invoiceStatusInprogress)->count(); // count status
        $inprogressTotal = (clone $invoiceStatusInprogress)->sum('total'); // sum total of invoices all

        /** where status = completed */
        $invoiceStatusCompleted = (clone $query)->where('status', filterHelper::INVOICE_STATUS['COMPLETED'])->orderBy('invoices.id', 'asc')->get();

        $invoiceStatusCompleted = filterHelper::getInvoicesStatus($invoiceStatusCompleted); // Apply transformation

        $completed = (clone $invoiceStatusCompleted)->count(); // count status
        $completedTotal = (clone $invoiceStatusCompleted)->sum('total'); // sum total of invoices all

        /** where status = canceled */
        $invoiceStatusCanceled = (clone $query)->where('status', filterHelper::INVOICE_STATUS['CANCELLED'])->orderBy('invoices.id', 'asc')->get();

        $invoiceStatusCanceled = filterHelper::getInvoicesStatus($invoiceStatusCanceled); // Apply transformation

        $canceled = (clone $invoiceStatusCanceled)->count(); // count status
        $canceledTotal = (clone $invoiceStatusCanceled)->sum('total'); // sum total of invoices all

        /** filter status */
        $query = filterHelper::invoiceFilterStatus($query, $request);


        $listInvoice = (clone $query)->orderBy('invoices.id', 'asc')->paginate($perPage);

        $listInvoice = filterHelper::mapDataInvoice($listInvoice); // Apply transformation

        return response()->json([
            'totalBill' => $totalBill,
            'totalPrice' => $totalPrice,
            'created' => [
                'amount' => $created,
                'total' => $createdTotal,
             ],
             'approved' => [
                'amount' => $approved,
                'total' => $approvedTotal,
             ],
            'inprogress' => [
               'amount' => $inprogress,
               'total' => $inprogressTotal,
            ],
            'completed' => [
                'amount' => $completed,
                'total' => $completedTotal,
             ],
             'canceled' => [
                'amount' => $canceled,
                'total' => $canceledTotal,
             ],
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
        //$perPage = $request->per_page;
        $invoiceId = $request->id;

        $item = DB::table('invoices')
            ->select(
                'invoices.*',
                DB::raw('(SELECT COUNT(*) FROM invoice_details WHERE invoice_details.invoice_id = invoices.id) as count_details'),
                DB::raw('(SELECT SUM(invoice_details.total) FROM invoice_details WHERE invoice_details.invoice_id = invoices.id) as total')
            )
            ->where('invoices.id', $invoiceId)
            ->first();

        $total = $item->total;

        $tax = $item->tax;
        $discount = $item->discount;

        // Calculate the tax amount
        $taxAmount = ($total * $tax) / 100;

        // Calculate the discount amount
        $discountAmount = ($total * $discount) / 100;

        // Calculate the final payable amount
        $sumTotal = ($total - $discountAmount) + $taxAmount;

        // Update the item with the calculated total
        $item->total = $sumTotal;

        /** loop data */
        TableHelper::formatDataInvoice($item);

        /**Detail */
        $details = InvoiceDetail::where('invoice_id', $invoiceId)->get();

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
