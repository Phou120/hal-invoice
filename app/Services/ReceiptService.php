<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Receipt;
use App\Models\InvoiceRate;
use App\Traits\ResponseAPI;
use App\Helpers\TableHelper;
use App\Helpers\filterHelper;
use App\Models\InvoiceDetail;
use App\Models\ReceiptDetail;
use App\Helpers\generateHelper;
use App\Models\Currency;
use App\Services\CalculateService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Services\returnData\ReturnService;

class ReceiptService
{
    use ResponseAPI;

    public $calculateService;
    public $returnService;

    public function __construct(
        CalculateService $calculateService,
        ReturnService $returnService
    )
    {
        $this->calculateService = $calculateService;
        $this->returnService = $returnService;
    }

    /** ບັນທຶກໃບຮັບເງິນ */
    public function addReceipt($request)
    {
        $getInvoiceRate = InvoiceRate::find($request['invoice_rate_id']);
        if($getInvoiceRate){
            $getInvoice = Invoice::where('id', $getInvoiceRate->invoice_id)->first();
            $invoice = $getInvoice->where('status', filterHelper::INVOICE_STATUS['COMPLETED'])->first();
            if(isset($invoice)){

                DB::beginTransaction();

                    $addReceipt = new Receipt();
                    $addReceipt->invoice_rate_id = $getInvoiceRate['id'];
                    $addReceipt->receipt_number = generateHelper::generateReceiptNumber('RN-', 8);
                    $addReceipt->receipt_name = $request['receipt_name'];
                    $addReceipt->receipt_date = $request['receipt_date'];
                    $addReceipt->created_by = Auth::user('api')->id;
                    $addReceipt->discount = $getInvoiceRate['discount'];
                    $addReceipt->tax = $getInvoiceRate['tax'];
                    $addReceipt->note = $request['note'];
                    $addReceipt->sub_total = $getInvoiceRate['sub_total'];
                    $addReceipt->total = $getInvoiceRate['total'];
                    $addReceipt->save();

                    $getInvoiceRate->status_create_receipt = 1;
                    $getInvoiceRate->save();

                DB::commit();

                return response()->json([
                    'error' => false,
                    'msg' => 'ສຳເລັດແລ້ວ'
                ], 200);
            }
            return response()->json([
                    'msg' => 'ສະຖານະຂອງໃບເກັບເງິນຄວນເປັນ completed ພວກເຮົາຈື່ງສາມາດອອກໃບຮັບເງິນໄດ້...'
            ], 422);
        }

        return response()->json([
            'error' => true,
            'msg' => 'Id not found...'
        ], 500);
    }

    /** ດຶງຂໍມູນໃບຮັບເງິນ */
    public function listReceipts($request)
    {
        $user = Auth::user();
        $perPage = $request->per_page;

        $query = Receipt::select('receipts.*', 'currencies.name as currency_name')
        ->join('invoice_rates', 'receipts.invoice_rate_id', 'invoice_rates.id')
        ->join('currencies', 'invoice_rates.currency_id', 'currencies.id');

        /** filter date */
        FilterHelper::receiptFilter($query, $request);

        $receipts = $query->orderBy('receipts.id', 'asc')->get();

        // Initialize currency totals array
        $currencyTotals = [];

        foreach ($receipts as $item) { // Use get() to fetch the results of the query
            // Calculate currency totals for the invoice
            $invoiceRates = InvoiceRate::where('id', $item->invoice_rate_id)->get();
            // return $invoiceRates;

            foreach ($invoiceRates as $rate) {
                $currencyId = $rate->currency_id;
                $currency = Currency::find($currencyId);
                // return $currency;

                if ($currency) {
                    $currencyShortName = $currency->short_name;

                    // Initialize currency totals if not exists
                    if (!isset($currencyTotals[$currencyShortName])) {
                        $currencyTotals[$currencyShortName] = [
                            'currency' => $currencyShortName,
                            'rate' => 0,
                            'total' => 0,
                        ];
                    }

                    // Use the currencyName variable to update the totals
                    $currencyTotals[$currencyShortName]['rate'] += $rate->rate;
                    $currencyTotals[$currencyShortName]['total'] += $rate->total;
                }
            }
        }

        // Convert currencyTotals array to a list
        $rateCurrencies = array_values($currencyTotals);

        if ($user->hasRole(['superadmin', 'admin'])) {
            // Superadmins and admins can see all receipts.
            $query->orderBy('receipts.id', 'asc');
            $countReceipt = $query->count();

            $listReceipt = $query->paginate($perPage);

            /** Merge data */
            $response = [
                'totalReceipt' => $countReceipt,
                'rate' => $rateCurrencies,
                'listReceipt' => $listReceipt
            ];

            return response()->json($response, 200);
        }

        if ($user->hasRole(['company-admin', 'company-user'])) {
            // Company-admin and company-user can only see their own receipts.
            $query->where('receipts.created_by', $user->id)->orderBy('receipts.id', 'asc');

            $countReceipt = $query->count();

            $listReceipt = $query->paginate($perPage);

            /** Merge data */
            $response = [
                'totalReceipt' => $countReceipt,
                'rate' => $rateCurrencies,
                'listReceipt' => $listReceipt
            ];

            return response()->json($response, 200);
        }
    }

    /** ດຶງຂໍມູນລາຍລະອຽດໃບຮັບເງິນ */
    // public function listReceiptDetail($request)
    // {
    //     $item = DB::table('receipts')
    //         ->select('receipts.*',
    //         DB::raw('(SELECT COUNT(*) FROM receipt_details WHERE receipt_details.receipt_id = Receipts.id) as count_details')
    //         )
    //         ->leftJoin('invoices', 'receipts.invoice_id', 'invoices.id')
    //         ->leftJoin('customers', 'receipts.customer_id', 'customers.id')
    //         ->leftJoin('currencies', 'receipts.currency_id', 'currencies.id')
    //         ->leftJoin('users', 'receipts.created_by', 'users.id')
    //         ->where('receipts.id', $request->id)
    //         ->orderBy('receipts.id', 'desc')
    //         ->first();

    //     /** loop data */
    //     TableHelper::loopDataOfReceipt($item);

    //     /**Detail */
    //     $details = DB::table('receipt_details')->where('receipt_id', $request->id)->get();

    //     return response()->json([
    //         'receipt' => $item,
    //         'details' => $details,
    //     ], 200);
    // }

    /** ແກ້ໄຂໃບຮັບເງິນ */
    public function editReceipt($request)
    {
        $editReceipt = Receipt::find($request['id']);

        if ($editReceipt) {
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

        return response()->json([
            'error' => true,
            'msg' => 'Id not found...'
        ], 404);
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

                /** update invoice_rate status_create_receipt */
                $updateInvoiceRate = InvoiceRate::find($receipt->invoice_rate_id);
                $updateInvoiceRate->status_create_receipt = 0;
                $updateInvoiceRate->save();

            DB::commit();

            return response()->json([
                'error' => false,
                'msg' => 'ສຳເລັດແລ້ວ'
            ], 200);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'error' => true,
                'msg' => 'Receipt not found...'
            ], 422);
        }
    }
}
