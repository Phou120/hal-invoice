<?php

namespace App\Services;
;
use App\Models\Invoice;
use App\Traits\ResponseAPI;
use App\Helpers\filterHelper;
use App\Models\InvoiceDetail;
use App\Helpers\generateHelper;
use App\Models\QuotationDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Services\returnData\ReturnService;


class InvoiceService
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

    /** ບັນທຶກໃບບິນເກັບເງິນ */
    public function addInvoice($request)
    {
        $quotationDetailId = $request['quotation_detail_id'];
        $quotationDetail = QuotationDetail::find($quotationDetailId);

        // $quotationId = $quotationDetail->where('quotation_id');

        if(isset($quotationDetail)) {
            $getQuotation = DB::table('quotations')
                ->select('quotations.*')
                ->join('quotation_details as quotation_detail', 'quotation_detail.quotation_id', '=', 'quotations.id')
                ->where('quotation_detail.id', $quotationDetailId)
                ->first();

            $getQuotationRate = DB::table('quotation_rates')
                ->select('quotation_rates.*')
                ->join('quotations', 'quotation_rates.quotation_id', '=', 'quotations.id')
                ->where('quotations.id', $getQuotation->id) // Use $getQuotation->id
                ->first(); // Use first() instead of get()

            if (count($quotationDetail) > 0) {

                DB::beginTransaction();

                    $addInvoice = new Invoice();
                    $addInvoice->invoice_number = generateHelper::generateInvoiceNumber('IV-', 8);
                    $addInvoice->invoice_name = $request['invoice_name'];
                    $addInvoice->quotation_id = $getQuotation->id; // Use object syntax
                    $addInvoice->customer_id = $getQuotation->customer_id; // Use object syntax
                    $addInvoice->currency_id = $getQuotationRate->currency_id; // Use object syntax
                    $addInvoice->start_date = $request['start_date'];
                    $addInvoice->discount = $getQuotationRate->discount; // Use object syntax
                    $addInvoice->end_date = $request['end_date'];
                    $addInvoice->note = $request['note'];
                    $addInvoice->created_by = Auth::user('api')->id;
                    $addInvoice->tax = filterHelper::TAX;
                    $addInvoice->save();

                    $taxRate = filterHelper::TAX;
                    $discountRate = $getQuotationRate->discount;
                    $sumSubTotal = 0;
                    foreach ($quotationDetail as $item) {
                            $total = $item['hour'] * $getQuotationRate->rate;

                            $addDetail = new InvoiceDetail();
                            $addDetail->order = $item['order'];
                            $addDetail->invoice_id = $addInvoice->id; // Use object syntax
                            $addDetail->name = $item['name'];
                            $addDetail->hour = $item['hour'];
                            $addDetail->rate = $getQuotationRate->rate;
                            $addDetail->description = $item['description'];
                            $addDetail->total = $total;
                            $addDetail->save();

                            $sumSubTotal += $total;

                            /** update quotation_detail status_create_invoice  */
                            $item->status_create_invoice = 1;
                            $item->save();
                        }
                        /**Calculate */
                        $this->calculateService->sumTotalInvoice($taxRate, $discountRate, $sumSubTotal, $addInvoice['id']);

                DB::commit();

                return response()->json([
                    'error' => false,
                    'msg' => 'ສຳເລັດແລ້ວ'
                ], 200);
            }
        } else {
            return response()->json([
                'error' => true,
                'msg' => 'Quotation detail not found.'
            ], 404);



            // $getTotalQuotation = $this->calculateService->sumTotalQuotation($request);
            // if(!$getTotalQuotation){
            //     $addInvoice = new Invoice();
            //     $addInvoice->invoice_number = generateHelper::generateInvoiceNumber('IV- ', 8);
            //     $addInvoice->invoice_name = $request['invoice_name'];
            //     $addInvoice->currency_id = $request['currency_id'];
            //     $addInvoice->quotation_id = $request['quotation_id'];
            //     $addInvoice->customer_id = $request['customer_id'];
            //     $addInvoice->start_date = $request['start_date'];
            //     $addInvoice->discount = $request['discount'];
            //     $addInvoice->end_date = $request['end_date'];
            //     $addInvoice->note = $request['note'];
            //     $addInvoice->created_by = Auth::user('api')->id;
            //     $addInvoice->tax = filterHelper::TAX;
            //     $addInvoice->save();

            //     if(!empty($request['invoice_details'])){
            //         foreach($request['invoice_details'] as $item){
            //             $addDetail = new InvoiceDetail();
            //             $addDetail->order = $item['order'];
            //             $addDetail->invoice_id = $addInvoice['id'];
            //             $addDetail->name = $item['name'];
            //             $addDetail->amount = $item['amount'];
            //             $addDetail->price = $item['price'];
            //             $addDetail->description = $item['description'];
            //             $addDetail->total = $item['amount'] * $item['price'];
            //             $addDetail->save();
            //         }
            //     }

                // DB::commit();

            //     return response()->json([
            //         'error' => false,
            //         'msg' => 'ສຳເລັດແລ້ວ'
            //     ], 200);
            // }else{
            //     return response()->json([
            //         'error' => true,
            //         'msg' => 'ທ່ານບໍ່ສາມາດສ້າງໃບເກັບເງິນເກີນນີ້ໄດ້: ' . $getTotalQuotation
            //     ], 422);
            // }

        }
    }

    /** ດຶງໃບບິນເກັບເງິນ */
    public function listInvoices($request)
    {
        $user = Auth::user();

        $perPage = $request->per_page;

        $query = Invoice::select(
            'invoices.*',
            DB::raw('(SELECT COUNT(*) FROM invoice_details WHERE invoice_details.invoice_id = invoices.id) as count_details'),
        );

        /** filter start_date and end_date */
        $query = filterHelper::filterDate($query, $request);

        if ($user->hasRole(['superadmin', 'admin'])) {
            $query->orderBy('invoices.id', 'asc');
        }
        if ($user->hasRole(['company-admin', 'company-user'])) {
            $query->where('invoices.created_by', $user->id);
        }

        $invoice = $query->orderBy('invoices.id', 'asc')->get();
        $totalBill = $invoice->count();
        $totalPrice = $invoice->sum('total');


        function filterQuotationStatus($query, $status) {
            return $query->where('status', filterHelper::INVOICE_STATUS[$status]);
        }

        /** where status = created */
        $invoiceStatusCreated = filterQuotationStatus(clone $query, 'CREATED');
        $created = $invoiceStatusCreated->count();
        $createdTotal = $invoiceStatusCreated->sum('total');

        /** where status = approved */
        $invoiceStatusApproved = filterQuotationStatus(clone $query, 'APPROVED');
        $approved = $invoiceStatusApproved->count();
        $approvedTotal = $invoiceStatusApproved->sum('total');

        /** where status = inprogress */
        $invoiceStatusInprogress = filterQuotationStatus(clone $query, 'INPROGRESS');
        $inprogress = $invoiceStatusInprogress->count();
        $inprogressTotal = $invoiceStatusInprogress->sum('total');

        /** where status = completed */
        $invoiceStatusCompleted = filterQuotationStatus(clone $query, 'COMPLETED');
        $completed = $invoiceStatusCompleted->count();
        $completedTotal = $invoiceStatusCompleted->sum('total');

        /** where status = canceled */
        $invoiceStatusCanceled = filterQuotationStatus(clone $query, 'CANCELLED');
        $canceled = $invoiceStatusCanceled->count();
        $canceledTotal = $invoiceStatusCanceled->sum('total');

        /** filter status */
        $query = filterHelper::filterStatus($query, $request);


        if ($user->hasRole(['superadmin', 'admin'])) {
            $listInvoice = $query->orderBy('invoices.id', 'asc');
        }

        if ($user->hasRole(['company-admin', 'company-user'])) {
            $listInvoice = $query
                ->where(function ($query) use ($user) {
                    $query->where('invoices.created_by', $user->id);
                })
                ->orderBy('invoices.id', 'asc');
        }

        $listInvoice = $listInvoice->paginate($perPage);

        $listInvoice = filterHelper::mapDataInvoice($listInvoice); // Apply transformation

        /** return data */
        $responseData = (new ReturnService())->responseInvoiceData(
            $totalBill, $totalPrice, $created, $createdTotal, $approved, $approvedTotal,
            $inprogress, $inprogressTotal, $completed, $completedTotal, $canceled, $canceledTotal, $listInvoice
        );

        return response()->json($responseData, 200);
    }

    /** list invoice to export PDF */
    public function listInvoice($id)
    {
        $invoice = Invoice::select([
            'invoices.*',
            DB::raw('(SELECT COUNT(*) FROM invoice_details WHERE invoice_details.invoice_id = invoices.id) as count_details')
        ])->where('id', $id)
        ->orderBy('id', 'desc')
        ->first();

        return $invoice->format();
    }

    /** ບັນທຶກລາຍລະອຽດໃບບິນ */
    public function addInvoiceDetail($request)
    {
        $quotationDetailId = $request->input('quotation_detail_id');
        $quotationDetail = QuotationDetail::find($quotationDetailId);

        if (!$quotationDetail) {
            return response()->json([
                'error' => true,
                'msg' => 'Quotation detail not found'
            ], 404);
        }

        /** select quotation_details */
        $quotation = (new ReturnService())->selectQuotation($quotationDetailId);

        if ($quotation) {
            $invoiceId = $request->input('id');

            /** query quotationRate */
            $getQuotationRate = (new ReturnService())->selectQuotationRate($quotationDetailId);
            /** data in invoice_details */
            // $invoiceDetails = (new ReturnService())->invoiceDetail($quotation, $invoiceId);

            $invoiceDetails = [
                'description' => $quotation->description,
                'invoice_id' => $invoiceId,
                'hour' => $quotation->hour,
                'rate' => $getQuotationRate->rate,
                'order' => $quotation->order,
                'name' => $quotation->name,
                'total' => $quotation->hour * $getQuotationRate->rate,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            /** insert invoice_details */
            InvoiceDetail::insert([$invoiceDetails]);

             /**Update Invoice */
            $editInvoice = Invoice::find($invoiceId);

            /**Update Calculate */
            $this->calculateService->calculateTotalInvoice_ByEdit($editInvoice);

            /** update quotation_detail status_create_invoice */
            foreach ($quotationDetail as $item) {
                $item->status_create_invoice = 1;
                $item->save();
            }
        }

        return response()->json([
            'error' => false,
            'msg' => 'ສຳເລັດແລ້ວ'
        ], 200);


        // $checkBalance = $this->calculateService->checkBalanceInvoice($request);
        // if(!$checkBalance){
        //     $addDetail = new InvoiceDetail();
        //     $addDetail->description = $request['description'];
        //     $addDetail->invoice_id = $request['id'];
        //     $addDetail->amount = $request['amount'];
        //     $addDetail->price = $request['price'];
        //     $addDetail->order = $request['order'];
        //     $addDetail->name = $request['name'];
        //     $addDetail->total = $request['amount'] * $request['price'];
        //     $addDetail->save();

        //     return response()->json([
        //         'error' => false,
        //         'msg' => 'ສຳເລັດແລ້ວ'
        //     ], 200);
        // }else{
        //     return response()->json([
        //         'error' => false,
        //         'msg' => 'ທ່ານບໍ່ສາມາດສ້າງໃບເກັບເງິນເກີນນີ້ໄດ້: ' . $checkBalance
        //     ], 422);
        // }
    }

    /** ດຶງລາຍລະອຽດໃບບິນ */
    public function listInvoiceDetail($request)
    {
        $user = Auth::user();
        $invoiceId = $request->id;

        $query = DB::table('invoices')
            ->select('invoices.*', DB::raw('(SELECT COUNT(*) FROM invoice_details WHERE invoice_details.invoice_id = invoices.id) as count_details'))
            ->leftJoin('customers', 'invoices.customer_id', 'customers.id')
            ->leftJoin('currencies', 'invoices.currency_id', 'currencies.id')
            ->leftJoin('users', 'invoices.created_by', 'users.id')
            ->where('invoices.id', $invoiceId);

        if ($user->hasRole(['superadmin', 'admin'])) {
            $query->orderBy('invoices.id', 'asc');
        }

        if ($user->hasRole(['company-admin', 'company-user'])) {
            $query->where('invoices.created_by', $user->id)
                ->orderBy('invoices.id', 'asc');
        }

        $item = $query->first();

        $detailsQuery = InvoiceDetail::select('invoice_details.*')
            ->join('invoices', 'invoice_details.invoice_id', 'invoices.id')
            ->where('invoice_id', $invoiceId);

        if ($user->hasRole(['superadmin', 'admin'])) {
            $detailsQuery->orderBy('invoices.id', 'asc');
        }

        if ($user->hasRole(['company-admin', 'company-user'])) {
            $detailsQuery->where('invoices.created_by', $user->id)
                ->orderBy('invoices.id', 'asc');
        }

        $details = $detailsQuery->get();

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
    // public function editInvoiceDetail($request)
    // {
    //     $checkBalance = $this->calculateService->checkBalanceInvoiceByEdit($request);
    //     if(!$checkBalance){
    //         $editDetail = InvoiceDetail::find($request['id']);
    //         $editDetail->order = $request['order'];
    //         $editDetail->name = $request['name'];
    //         $editDetail->amount = $request['amount'];
    //         $editDetail->price = $request['price'];
    //         $editDetail->description = $request['description'];
    //         $editDetail->total = $request['amount'] * $request['price'];
    //         $editDetail->save();

    //         return response()->json([
    //             'error' => false,
    //             'msg' => 'ສຳເລັດແລ້ວ'
    //         ], 200);
    //     }else{
    //         return response()->json([
    //             'error' => false,
    //             'msg' => 'ທ່ານບໍ່ສາມາດສ້າງໃບເກັບເງິນເກີນນີ້ໄດ້: ' . $checkBalance
    //         ], 422);
    //     }
    // }

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

            $invoice = Invoice::findOrFail($request['id']);
                $invoice->updated_by = Auth::user('api')->id;
                $invoice->save();

                 // Delete the InvoiceDetails and the Invoice model
                $invoice->delete();
                InvoiceDetail::where('invoice_id', $request['id'])->delete();


                // Find the Invoice model
                // $invoice = Invoice::findOrFail($request['id']);
                // $invoice->updated_by = Auth::user('api')->id;
                // if($invoice['quotation_id']){
                //     $getQuotation = Quotation::find($invoice['quotation_id']);
                //     if($getQuotation){
                //         $getQuotation->total += $invoice['total'];
                //         $getQuotation->save();
                //     }
                // }
                // $invoice->save();

                //  // Delete the InvoiceDetails and the Invoice model
                // $invoice->delete();
                // InvoiceDetail::where('invoice_id', $request['id'])->delete();

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
