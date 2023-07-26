<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Receipt;
use App\Helpers\myHelper;
use App\Models\Quotation;
use App\Traits\ResponseAPI;
use App\Models\InvoiceDetail;
use App\Models\PurchaseOrder;
use App\Models\ReceiptDetail;
use App\Models\PurchaseDetail;
use App\Models\QuotationDetail;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

class CalculateService
{
    use ResponseAPI;

    public function checkBalanceByDetail($data)
    {
        $total = $data['amount'] * $data['price'];
        $detail = InvoiceDetail::where('id', $data->id)->first();
        $getInvoice = Invoice::where('id', $detail->invoice_id)->first();
        $quotation = Quotation::where('id', $getInvoice['quotation_id'])->first();
        $sumTotalInvoice = Invoice::where('quotation_id', $getInvoice['quotation_id'])->sum('sub_total');
        $sumTotal = ($quotation['total']) - ($sumTotalInvoice - $detail['total']);

        if($sumTotal >= $total) {
            return null;
        }

        return $sumTotal;
    }

    public function checkDetail($data)
    {
        $total = $data['amount'] * $data['price'];
        $getInvoice = Invoice::where('id', $data->id)->first();
        $quotation = Quotation::where('id', $getInvoice['quotation_id'])->first();
        $sumTotalInvoice = Invoice::where('quotation_id', $getInvoice['quotation_id'])->sum('sub_total');
        $sumTotal = $quotation['total'] - $sumTotalInvoice;

        if($sumTotal >= $total) {
            return null;
        }

        return $sumTotal;
    }

    public function checkInvoice($data)
    {
        $total = collect($data['invoice_details'])->sum(function ($detail) {
            return $detail['amount'] * $detail['price'];
        });

        $getQuotation = Quotation::where('id', $data['quotation_id'])->first();
        $totalInvoice = Invoice::where('quotation_id', $data['quotation_id'])->sum('sub_total');
        $sumTotal = $getQuotation['total'] - $totalInvoice;
        if($sumTotal >= $total) {
            return null;
        }

        return $sumTotal;
    }
    /** calculate quotation */
    public function calculateTotal($request, $sumSubTotal, $id)
    {
        /** Calculate **/
        $sumTotalTax = $sumSubTotal * myHelper::TAX / 100;
        $sumTotalDiscount = $sumSubTotal * $request['discount'] / 100;
        $sumTotal = ($sumSubTotal - $sumTotalDiscount) + $sumTotalTax;

        /** Update Total Quotation */
        $addQuotation = Quotation::find($id);
        $addQuotation->tax = myHelper::TAX;
        $addQuotation->sub_total = $sumSubTotal;
        $addQuotation->total = $sumTotal;
        $addQuotation->save();
    }

    /** edit calculate quotation */
    public function calculateTotal_ByEdit($quotation)
    {
        /** Calculate */
        $sumSubTotalPrice = QuotationDetail::where('quotation_id', $quotation['id'])->get()->sum('total');
        $sumTotalTax = $sumSubTotalPrice * myHelper::TAX / 100;
        $sumTotalDiscount = $sumSubTotalPrice * $quotation['discount'] / 100;
        $sumTotal = ($sumSubTotalPrice - $sumTotalDiscount) + $sumTotalTax;

        /** Update Total Quotation */
        $editQuotation = Quotation::find($quotation['id']);
        $editQuotation->tax = myHelper::TAX;
        $editQuotation->sub_total = $sumSubTotalPrice;
        $editQuotation->total = $sumTotal;
        $editQuotation->save();
    }

    /** calculate Invoice */
    public function calculateTotalInvoice($request, $sumSubTotal, $id)
    {
        /** Calculate */
        $sumTotalTax = $sumSubTotal * myHelper::TAX / 100;
        $sumTotalDiscount = $sumSubTotal * $request['discount'] / 100;
        // $sumTotal = ($sumSubTotal - $sumTotalDiscount) + $sumTotalTax;
        $sumTotal = ($sumSubTotal);

        /** Update Total Invoice */
        $addInvoice = Invoice::find($id);
        $addInvoice->tax = myHelper::TAX;
        $addInvoice->sub_total = $sumSubTotal;
        $addInvoice->total = $sumTotal;
        $addInvoice->save();
    }

    /** edit calculate Invoice */
    public function calculateTotalInvoice_ByEdit($request, $detail, $total)
    {
        /** Calculate */
        // $sumTotalTax = $sumSubTotalPrice * myHelper::TAX / 100;
        // $sumTotalDiscount = $sumSubTotalPrice * $request['discount'] / 100;
        // // $sumTotal = ($sumSubTotalPrice - $sumTotalDiscount) + $sumTotalTax;
        $sumTotal = ($request['total'] - $total);

        /** Update Total Invoice */
        $editInvoice = Invoice::find($request['id']);
        $editInvoice->tax = myHelper::TAX;
        $editInvoice->sub_total = $sumTotal + $detail['total'];
        $editInvoice->total = $sumTotal + $detail['total'];
        $editInvoice->save();

        if($editInvoice['quotation_id']){
            $updateQuotation = Quotation::find($editInvoice['quotation_id']);
            if($updateQuotation['total'] >= $editInvoice['total']){
                return null;
            } else {
               return $updateQuotation['total'];
            }
        }
    }

     /** calculate Receipt */
     public function calculateTotalReceipt($request, $sumSubTotal, $id)
     {
         /** Calculate */
         $sumTotalTax = $sumSubTotal * myHelper::TAX / 100;
         $sumTotalDiscount = $sumSubTotal * $request['discount'] / 100;
         $sumTotal = ($sumSubTotal - $sumTotalDiscount) + $sumTotalTax;

         /** Update Total Receipt */
         $addQuotation = Receipt::find($id);
         $addQuotation->sub_total = $sumSubTotal;
         $addQuotation->total = $sumTotal;
         $addQuotation->save();
     }

     /** edit calculate Receipt */
     public function calculateTotalReceipt_ByEdit($request)
     {
         /** Calculate */
         $sumSubTotalPrice = ReceiptDetail::where('receipt_id', $request['id'])->get()->sum('total');
         $sumTotalTax = $sumSubTotalPrice * myHelper::TAX / 100;
         $sumTotalDiscount = $sumSubTotalPrice * $request['discount'] / 100;
         $sumTotal = ($sumSubTotalPrice - $sumTotalDiscount) + $sumTotalTax;

         /** Update Total Receipt */
         $editQuotation = Receipt::find($request['id']);
         $editQuotation->sub_total = $sumSubTotalPrice;
         $editQuotation->total = $sumTotal;
         $editQuotation->save();
     }

     /** calculate PurchaseOrder */
     public function calculateTotalOrder($request, $sumSubTotal, $id)
     {
         /** Calculate */
         $sumTotalTax = $sumSubTotal * myHelper::TAX / 100;
         $sumTotalDiscount = $sumSubTotal * $request['discount'] / 100;
         $sumTotal = ($sumSubTotal - $sumTotalDiscount) + $sumTotalTax;

         /** Update Total PurchaseOrder */
         $addQuotation = PurchaseOrder::find($id);
         $addQuotation->sub_total = $sumSubTotal;
         $addQuotation->total = $sumTotal;
         $addQuotation->save();
     }

     /** edit calculate PurchaseOrder */
     public function calculateTotalOrder_ByEdit($request)
     {
         /** Calculate */
         $sumSubTotalPrice = PurchaseDetail::where('purchase_id', $request['id'])->get()->sum('total');
         $sumTotalTax = $sumSubTotalPrice * myHelper::TAX / 100;
         $sumTotalDiscount = $sumSubTotalPrice * $request['discount'] / 100;
         $sumTotal = ($sumSubTotalPrice - $sumTotalDiscount) + $sumTotalTax;

         /** Update Total PurchaseOrder */
         $editQuotation = PurchaseOrder::find($request['id']);
         $editQuotation->sub_total = $sumSubTotalPrice;
         $editQuotation->total = $sumTotal;
         $editQuotation->save();
     }
}
