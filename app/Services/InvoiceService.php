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

        // Define an array to store currency totals
        $currencyTotals = [];

        // Define invoice statuses
        $statuses = filterHelper::INVOICE_STATUS;

        // Initialize status data
        $statusData = array_fill_keys($statuses, [
            'totalDetail' => 0,
            'rates' => [],
        ]);

        // Get all invoices with date filtering
        $query = DB::table('invoices')
            ->select('invoices.*',
                DB::raw('(SELECT COUNT(id) FROM companies) as company_count'),
                DB::raw('(SELECT COUNT(id) FROM customers) as customer_count')
            );

        /** filter date */
        FilterHelper::filterDate($query, $request);

        $invoices = $query ->orderBy('invoices.id', 'asc')->get();

        foreach ($invoices as $invoice) {
            // Count details for each invoice
            $invoice->countDetail = DB::table('invoice_details')
                ->where('invoice_id', $invoice->id)
                ->whereNull('deleted_at')
                ->count();

            // Calculate currency totals for the invoice
            $invoiceRates = DB::table('invoice_rates')
                ->where('invoice_id', $invoice->id)
                ->get();

                if ($invoice->countDetail === 0) {
                    continue;
                }

            foreach ($invoiceRates as $rate) {
                $currencyId = $rate->currency_id;
                $currency = DB::table('currencies')->find($currencyId);

                if ($currency) {
                    $currencyName = $currency->short_name;

                    // Initialize currency totals if not exists
                    if (!isset($currencyTotals[$currencyName])) {
                        $currencyTotals[$currencyName] = [
                            'currency' => $currencyName,
                            'rate' => 0,
                            'total' => 0,
                        ];
                    }

                    $currencyTotals[$currencyName]['rate'] += $rate->rate;
                    $currencyTotals[$currencyName]['total'] += $rate->total;
                }
            }

            // Update status totals
            $invoiceStatus = in_array($invoice->status, $statuses) ? $invoice->status : 'unknown';

            $statusData[$invoiceStatus]['totalDetail'] += $invoice->countDetail;

            // Calculate rates for the current status
            foreach ($invoiceRates as $rate) {
                $currencyId = $rate->currency_id;
                $currency = DB::table('currencies')->find($currencyId);

                if ($currency) {
                    $currencyName = $currency->short_name;

                    // Initialize status totals if not exists
                    if (!isset($statusData[$invoiceStatus]['rates'][$currencyName])) {
                        $statusData[$invoiceStatus]['rates'][$currencyName] = [
                            'currency' => $currencyName,
                            'rate' => 0,
                            'total' => 0,
                        ];
                    }

                    // Update status totals
                    $statusData[$invoiceStatus]['rates'][$currencyName]['rate'] += $rate->rate;
                    $statusData[$invoiceStatus]['rates'][$currencyName]['total'] += $rate->total;
                }
            }
        }

        foreach ($statuses as $status) {
            if ($statusData[$status]['totalDetail'] === 0) {
                $statusData[$status]['rates'] = [];
            }
        }

        // Filter out currencies with zero rates for each status
        foreach ($statusData as &$status) {
            if (is_array($status) && isset($status['rates'])) {
                $status['rates'] = array_values(array_filter($status['rates'], function ($currency) {
                    return is_array($currency) && isset($currency['rate']);
                }));
            }
        }

        // Sort currencyTotals by rate in descending order and limit to the top three
        $rateCurrencies = collect($currencyTotals)->values()->all();

        /** filter status */
        filterHelper::filterStatusOfInvoice($query, $request);

        /** filter DI */
        filterHelper::filterIDInvoice($query, $request);

        //  /** filter Name */
        filterHelper::filterInvoiceName($query, $request);

        if ($user->hasRole(['superadmin', 'admin'])) {
            // Allow superadmin and admin to see all data
            $listInvoice = $query->orderBy('invoices.id', 'desc');

            /** do paginate */
            $getInvoice = $listInvoice->paginate($perPage);

            // Map data
            $mapInvoice = $this->returnService->mapDataInQuotation($getInvoice);

            /** merge invoice data of super-admin and admin */
            $responseData = [
                'company_count' => $invoices->isEmpty() ? 0 : $invoices[0]->company_count,
                'customer_count' => $invoices->isEmpty() ? 0 : $invoices[0]->customer_count,
                'totalDetail' => array_sum(array_column($statusData, 'totalDetail')),
                'rate' => $rateCurrencies,
            ] + $statusData + [
                'listInvoice' => $mapInvoice,
            ];

            return response()->json($responseData, 200);
        }

        if ($user->hasRole(['company-admin', 'company-user'])) {
            // Filter invoices for company-admin and company-user based on user ID
            $listInvoice = $query->where('created_by', $user->id)->orderBy('invoices.id', 'asc');

            $getInvoice = $listInvoice->paginate($perPage);

            // Map data
            $mapInvoice = $this->returnService->mapDataInQuotation($getInvoice);

            /** merge invoice data of user */
            $responseData = [
                'totalDetail' => array_sum(array_column($statusData, 'totalDetail')),
                'rate' => $rateCurrencies,
            ] + $statusData + [
                'listInvoice' => $mapInvoice,
            ];

            return response()->json($responseData, 200);
        }
    }

    /** list invoice to export PDF */
    public function listInvoice($request)
    {
        $rateId = $request->id;
        $currencyId = $request->currency_id;

        $invoiceRate = InvoiceRate::find($rateId);

        $currency = Currency::find($currencyId);

        if ($currency === null) {
            return response()->json('currency name not found...', 422); // or handle the error as needed
        }

        $name = $currency->name;

        $invoice = Invoice::select([
            'invoices.*',
            'currencies.name as currencyName',
            'currencies.short_name as currencyShortName',
            'invoice_rates.sub_total as rateSubTotal',
            'invoice_rates.discount as rateDiscount',
            'invoice_rates.tax as rateTax',
            'invoice_rates.total as rateTotal',
            'invoice_rates.rate as rate',
            DB::raw('(SELECT COUNT(*) FROM invoice_details WHERE invoice_details.invoice_id = invoices.id) as count_details')
        ])
        ->join('invoice_rates', 'invoice_rates.invoice_id', 'invoices.id')
        ->join('currencies', 'invoice_rates.currency_id', 'currencies.id')
        ->where('invoice_rates.invoice_id', $invoiceRate->invoice_id)
        ->where('currencies.name', $name)
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

        // merge data
        $response = [
            'countDetail' => $countDetail,
            'rate' => $rateCurrencies,
            'invoice' => $mapInvoice,
            'details' => $details,
        ];

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
            $invoiceId = $deleteDetail->invoice_id;

            // Find the Invoice by ID
            $invoice = Invoice::find($invoiceId);
            $invoice->updated_by = Auth::user('api')->id;
            $invoice->save();

            // Delete the InvoiceDetail
            $deleteDetail->delete();

            // Update the quotation_detail status to indicate that the invoice is created
            filterHelper::updateQuotationDetailStatusCreatedInvoice($deleteDetail);

            // Calculate the sum of hours for the invoice
            $sumHour = InvoiceDetail::where('invoice_id', $invoiceId)->sum('hour');

            // Get the QuotationRates related to this invoice
            $invoiceRate = InvoiceRate::where('invoice_id', $invoiceId)->get();

            // Calculate and update quotation details and rates
            $this->calculateService->updateQuotationDetailAndQuotationRate($invoiceRate, $sumHour);

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

            /** delete invoice */
                $invoice = Invoice::findOrFail($request['id']);
                $invoice->updated_by = Auth::user('api')->id;
                $invoice->save();

                /** delete invoiceDetail */
                $invoiceDetail = InvoiceDetail::where('invoice_id', $invoice['id'])->get();
                foreach ($invoiceDetail as $detail) {
                    if($detail){
                        // dd('dd');
                        /** update quotation_detail status create invoice */
                        filterHelper::updateQuotationDetailStatusCreatedInvoice($detail);
                        $detail->delete();
                    }
                }

                /** delete invoice_rate */
                InvoiceRate::where('invoice_id', $invoice['id'])->delete();

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
