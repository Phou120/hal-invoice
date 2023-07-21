<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Receipt;
use App\Models\Quotation;
use App\Traits\ResponseAPI;
use App\Models\InvoiceDetail;
use App\Models\PurchaseOrder;
use App\Models\ReceiptDetail;
use App\Models\PurchaseDetail;
use App\Models\QuotationDetail;

class CalculateService
{
    use ResponseAPI;

    /** calculate quotation */
    public function calculateTotal($request, $sumSubTotal, $id)
    {
        /** Calculate **/
        $sumTotalTax = $sumSubTotal * $request['tax'] / 100;
        $sumTotalDiscount = $sumSubTotal * $request['discount'] / 100;
        $sumTotal = ($sumSubTotal - $sumTotalDiscount) + $sumTotalTax;

        /** Update Total Quotation */
        $addQuotation = Quotation::find($id);
        $addQuotation->sub_total = $sumSubTotal;
        $addQuotation->total = $sumTotal;
        $addQuotation->save();
    }

    /** edit calculate quotation */
    public function calculateTotal_ByEdit($quotation)
    {
        /** Calculate */
        $sumSubTotalPrice = QuotationDetail::where('quotation_id', $quotation['id'])->get()->sum('total');
        $sumTotalTax = $sumSubTotalPrice * $quotation['tax'] / 100;
        $sumTotalDiscount = $sumSubTotalPrice * $quotation['discount'] / 100;
        $sumTotal = ($sumSubTotalPrice - $sumTotalDiscount) + $sumTotalTax;

        /** Update Total Quotation */
        $editQuotation = Quotation::find($quotation['id']);
        $editQuotation->sub_total = $sumSubTotalPrice;
        $editQuotation->total = $sumTotal;
        $editQuotation->save();
    }

    /** calculate Invoice */
    public function calculateTotalInvoice($request, $sumSubTotal, $id)
    {
        /** Calculate */
        $sumTotalTax = $sumSubTotal * $request['tax'] / 100;
        $sumTotalDiscount = $sumSubTotal * $request['discount'] / 100;
        $sumTotal = ($sumSubTotal - $sumTotalDiscount) + $sumTotalTax;

        /** Update Total Invoice */
        $addQuotation = Invoice::find($id);
        $addQuotation->sub_total = $sumSubTotal;
        $addQuotation->total = $sumTotal;
        $addQuotation->save();
    }

    /** edit calculate Invoice */
    public function calculateTotalInvoice_ByEdit($request)
    {
        /** Calculate */
        $sumSubTotalPrice = InvoiceDetail::where('invoice_id', $request['id'])->get()->sum('total');
        $sumTotalTax = $sumSubTotalPrice * $request['tax'] / 100;
        $sumTotalDiscount = $sumSubTotalPrice * $request['discount'] / 100;
        $sumTotal = ($sumSubTotalPrice - $sumTotalDiscount) + $sumTotalTax;

        /** Update Total Invoice */
        $editQuotation = Invoice::find($request['id']);
        $editQuotation->sub_total = $sumSubTotalPrice;
        $editQuotation->total = $sumTotal;
        $editQuotation->save();
    }

     /** calculate Receipt */
     public function calculateTotalReceipt($request, $sumSubTotal, $id)
     {
         /** Calculate */
         $sumTotalTax = $sumSubTotal * $request['tax'] / 100;
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
         $sumTotalTax = $sumSubTotalPrice * $request['tax'] / 100;
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
         $sumTotalTax = $sumSubTotal * $request['tax'] / 100;
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
         $sumTotalTax = $sumSubTotalPrice * $request['tax'] / 100;
         $sumTotalDiscount = $sumSubTotalPrice * $request['discount'] / 100;
         $sumTotal = ($sumSubTotalPrice - $sumTotalDiscount) + $sumTotalTax;

         /** Update Total PurchaseOrder */
         $editQuotation = PurchaseOrder::find($request['id']);
         $editQuotation->sub_total = $sumSubTotalPrice;
         $editQuotation->total = $sumTotal;
         $editQuotation->save();
     }
}
