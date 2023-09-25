<?php

namespace App\Services;
;
use App\Models\Invoice;
use App\Models\Currency;
use App\Models\InvoiceRate;
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

            if (!$quotationDetail) {
                return response()->json([
                    'error' => true,
                    'msg' => 'Quotation detail not found.'
                ], 404);
            }

        DB::beginTransaction();

            $getQuotation = DB::table('quotations')
                ->select('quotations.*')
                ->join('quotation_details as quotation_detail', 'quotation_detail.quotation_id', '=', 'quotations.id')
                ->where('quotation_detail.id', $quotationDetailId)
                ->first();

            $getQuotationRate = DB::table('quotation_rates')
                ->select('quotation_rates.*')
                ->join('quotations', 'quotation_rates.quotation_id', '=', 'quotations.id')
                ->where('quotations.id', $getQuotation->id)
                ->get();

            // Create the Invoice
            $addInvoice = new Invoice();
            $addInvoice->invoice_number = generateHelper::generateInvoiceNumber('IV-', 8);
            $addInvoice->invoice_name = $request['invoice_name'];
            $addInvoice->quotation_id = $getQuotation->id;
            $addInvoice->customer_id = $getQuotation->customer_id;
            $addInvoice->start_date = $request['start_date'];
            $addInvoice->end_date = $request['end_date'];
            $addInvoice->note = $request['note'];
            $addInvoice->created_by = Auth::user('api')->id;
            $addInvoice->save();

            /** update type_quotation in invoice */
            $this->returnService->updateTypeQuotationInInvoice($getQuotation, $addInvoice);

            $totalHours = 0;

            foreach ($quotationDetailId as $detailId) {
                // Find the corresponding QuotationDetail
                $quotationDetail = QuotationDetail::find($detailId);
                // dd($quotationDetail);

                if (!$quotationDetail) {
                    return response()->json(['error' =>true, 'message' => 'quotation_detail_id not found in quotation_detail']);
                }

                $addDetail = new InvoiceDetail();
                $addDetail->order = $quotationDetail->order;
                $addDetail->invoice_id = $addInvoice->id;
                $addDetail->name = $quotationDetail->name;
                $addDetail->hour = $quotationDetail->hour;
                $addDetail->description = $quotationDetail->description;
                $addDetail->quotation_detail_id = $detailId; // Assign the current ID

                $addDetail->save();

                $totalHours += $quotationDetail->hour;

                // Update quotation_detail status_create_invoice
                $quotationDetail->status_create_invoice = 1;
                $quotationDetail->save();
            }

            foreach ($getQuotationRate as $quotationRate) {
                $discount = $quotationRate->discount;
                $addInvoiceRate = new InvoiceRate();
                $addInvoiceRate->invoice_id = $addInvoice->id;
                $addInvoiceRate->currency_id = $quotationRate->currency_id;
                $addInvoiceRate->rate = $quotationRate->rate;
                $addInvoiceRate->sub_total =  $totalHours * $quotationRate->rate;
                $addInvoiceRate->discount = $discount;
                $addInvoiceRate->save();

                /**Calculate */
                $this->calculateService->calculateInvoice($discount, $addInvoiceRate['sub_total'], $addInvoiceRate['id']);
            }

        DB::commit();

        return response()->json([
            'error' => false,
            'msg' => 'ສຳເລັດແລ້ວ'
        ], 200);
    }

    /** ດຶງໃບບິນເກັບເງິນ */
    public function listInvoices($request)
    {
        $perPage = $request->per_page;
        $user = Auth::user();

        $query = Invoice::select('invoices.*');

        /** filter start_date and end_date */
        $query = filterHelper::filterDate($query, $request);

        /** check role in invoice */
        $this->returnService->checkRoleInvoice($query, $user);

        $invoice = $query->groupBy('invoices.id')->orderBy('invoices.id', 'asc')->get();

        $currencyTotals = [];

        // Calculate the total count of details (if needed)
        $totalDetail = 0;

        $invoice->map(function ($item) use (&$currencyTotals, &$totalDetail) {
            // Calculate the count of details associated with the quotation
            $item->countDetail = InvoiceDetail::where('invoice_id', $item->id)->count();
            $totalDetail += $item->countDetail;
            // Retrieve the rates associated with the quotation
            $invoiceRates = InvoiceRate::where('invoice_id', $item->id)->get();

            // Iterate over the rates and accumulate currency totals
            $invoiceRates->map(function ($rate) use (&$currencyTotals) {
                $currencyId = $rate->currency_id;
                $currency = Currency::find($currencyId);

                if ($currency) {
                    $currencyName = $currency->short_name;

                    if (!isset($currencyTotals[$currencyId])) {
                        $currencyTotals[$currencyId] = [
                            'currency' => $currencyName,
                            'rate' => 0,
                            'total' => 0,
                        ];
                    }

                    $currencyTotals[$currencyId]['rate'] += $rate->rate;
                    $currencyTotals[$currencyId]['total'] += $rate->total;
                }
            });
        });

        // Sort the currencyTotals by rate in descending order and limit to the top three
        $rateCurrencies = collect($currencyTotals)->values()->all();

         // $statuses = ["created", "approved", "inprogress", "completed", "cancelled"];
         $statuses = filterHelper::INVOICE_STATUS;

         // Initialize an empty array to store the results
         $statusTotals = [];

         // Loop through each status
         foreach ($statuses as $status) {
             // Filter quotations by status
             $invoiceStatus = Invoice::where('status', $status);

             /** check role */
             $this->returnService->checkRoleInvoice($invoiceStatus, $user);

             $invoice = $invoiceStatus->get();
             // Initialize an array for the rates for the current status
             $statusRates = [];
             $countDetail = 0;

             // Loop through each quotation for the current status
             foreach ($invoice as $item) {
                 $detailCounts = InvoiceDetail::where('invoice_id', $item->id)->count();
                 $countDetail += $detailCounts;

                 // Retrieve quotation rates for the current quotation
                 $invoiceRates = InvoiceRate::where('invoice_id', $item->id)->get();

                 foreach ($invoiceRates as $rate) {
                     $currencyId = $rate->currency_id;

                     // Check if the currency exists
                     $currency = Currency::find($currencyId);
                     if ($currency) {
                         $currencyName = $currency->short_name;

                         // Initialize the currency entry if it doesn't exist
                         if (!isset($statusRates[$currencyName])) {
                             $statusRates[$currencyName] = [
                                 'currency' => $currencyName,
                                 'rate' => 0,
                                 'total' => 0,
                             ];
                         }
                         // Update the currency entry with rate and total values
                         $statusRates[$currencyName]['rate'] += $rate->rate;
                         $statusRates[$currencyName]['total'] += $rate->total;
                     }
                 }
             }

             // Create an array for the current status with totalDetail and rates
             $statusTotals[$status] = [
                 'totalDetail' => $countDetail,
                 'rates' => array_values($statusRates), // Convert associative array to indexed array
             ];
         }

         /** filter status */
         $query = filterHelper::filterStatus($query, $request);

         /** filter DI */
         $query = filterHelper::filterIDInvoice($query, $request);

        //  /** filter Name */
         $query = filterHelper::filterInvoiceName($query, $request);

        if ($user->hasRole(['superadmin', 'admin'])) {
            $listInvoice = $query->orderBy('invoices.id', 'asc');

            // return $countUserCompany;
            $countUserCompany = $this->returnService->countUserCompany($query);

            $getInvoice = $listInvoice->paginate($perPage);

            /** map data */
            $mapInvoice = $this->returnService->mapDataInQuotation($getInvoice);

            /** return data */
            $responseData = $this->returnService->invoiceDataRole(
                $totalDetail, $statusTotals, $rateCurrencies, $mapInvoice, $countUserCompany
            );

            return response()->json($responseData, 200);
        }

        if ($user->hasRole(['company-admin', 'company-user'])) {
            $listInvoice = $query
            ->where(function ($query) use ($user) {
                $query->where('invoices.created_by', $user->id);
            })
            ->orderBy('invoices.id', 'asc');

            $getInvoice = $listInvoice->paginate($perPage);

            /** map data */
            $mapInvoice = $this->returnService->mapDataInQuotation($getInvoice);

            /** return data */
            $responseData = $this->returnService->invoiceData(
                $totalDetail, $statusTotals, $rateCurrencies, $mapInvoice
            );

            return response()->json($responseData, 200);
        }
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

        DB::beginTransaction();

            foreach ($quotationDetailId as $detailId) {
                // Find the corresponding QuotationDetail
                $quotationDetail = QuotationDetail::find($detailId);
                // dd($quotationDetail);

                if (!$quotationDetail) {
                    return response()->json(['error' =>true, 'message' => 'quotation_detail_id not found in quotation_detail']);
                }
                $hour = $quotationDetail->hour;

                $addDetail = new InvoiceDetail();
                $addDetail->order = $quotationDetail->order;
                $addDetail->invoice_id = $request->id;
                $addDetail->name = $quotationDetail->name;
                $addDetail->hour = $hour;
                $addDetail->description = $quotationDetail->description;
                $addDetail->quotation_detail_id = $detailId; // Assign the current ID

                $addDetail->save();
                // return $addDetail;

                // $totalHours += $quotationDetail->hour;

                // Update quotation_detail status_create_invoice
                $quotationDetail->status_create_invoice = 1;
                $quotationDetail->save();
            }

            /** update created_by in invoice */
            filterHelper::updateCreatedByInInvoice($addDetail);

            // Find the QuotationRate records with the matching quotation_id
            $invoiceRates = InvoiceRate::where('invoice_id', $request['id'])->get();
            // return $invoiceRates;

            /** calculate */
            $this->calculateService->calculateAndUpdateQuotationRates($invoiceRates, $hour);

        DB::commit();

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

        $query = DB::table('invoices')->select('invoices.*')->where('invoices.id', $invoiceId);

       /** check role in invoice */
       $this->returnService->checkRoleInvoice($query, $user);

        $item = $query->orderBy('invoices.id', 'asc')->get();

        // Get the count of quotation details
        $countDetail = $this->returnService->countDetailInvoice($request);
        // Get quotation rates for the given quotation
        $invoiceRates = $this->returnService->invoiceRate($request);
        // Use collections to group by currency and calculate sums
        $currencyTotals = $this->returnService->currencyTotals($invoiceRates);
        // Sort the resulting collection by rate in descending order
        $rateCurrencies = $currencyTotals->sortByDesc('rate')->values()->toArray();

        /** map data */
        $mapInvoice = $this->returnService->mapDataInQuotation($item);

        // Detail query
        $detailsQuery = $this->returnService->invoiceDetailsQuery($request);

        /** check role in invoice */
        $this->returnService->checkRoleInvoice($query, $user);

        // Get the results
        $details = $detailsQuery->get();

        /** sum data */
       $response = $this->returnService->outputInvoiceData($countDetail,$rateCurrencies, $mapInvoice , $details);

       return response()->json($response, 200);
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
        try {
            DB::beginTransaction();

            // Find the InvoiceDetail by ID
            $deleteDetail = InvoiceDetail::findOrFail($request['id']);

            // Update the createdBy in the InvoiceDetail
            filterHelper::updateCreatedByInInvoice($deleteDetail);

            // Delete the InvoiceDetail
            $deleteDetail->delete();

            /** update quotation_detail status create invoice */
            filterHelper::updateQuotationDetailStatusCreatedInvoice($deleteDetail);

            DB::commit();

            return response()->json([
                'error' => false,
                'msg' => 'ສຳເລັດແລ້ວ'
            ], 200);

        } catch (\Exception $e) {
            // Something went wrong, so rollback the transaction
            DB::rollback();

            // You can handle the exception here, log it, or return an error response
            return response()->json(['error' => true, 'msg' => 'not found...'], 500);
        }
    }

    /** ລຶບໃບບິນເກັບເງິນ */
    public function deleteInvoice($request)
    {
        try {

            DB::beginTransaction();

                $invoice = Invoice::findOrFail($request['id']);
                $invoice->updated_by = Auth::user('api')->id;
                $invoice->save();

                /** delete invoiceDetail */
                $invoiceDetail = InvoiceDetail::where('invoice_id', $request['id'])->get();

                foreach ($invoiceDetail as $detail) {
                    /** update quotation_detail status create invoice */
                    filterHelper::updateQuotationDetailStatusCreatedInvoice($detail);

                    $detail->delete();
                }

                $invoice->delete();

                /** update quotation_detail status create invoice */
                // filterHelper::updateQuotationDetailStatusCreatedInvoice($invoiceDetail);
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
                'msg' => 'id not found...'
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
