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

      /** Sum Total Quotation */
    public function sumTotalQuotation($data)
      {
        $total = collect($data['invoice_details'])->sum(function ($detail) {
            return $detail['amount'] * $detail['price'];
        });

        $totalQuotation = Quotation::where('id', $data['quotation_id'])->sum('total');
        $invoice = Invoice::where('quotation_id', $data['quotation_id'])->get();
        $totalInvoice = InvoiceDetail::whereIn('invoice_id', $invoice->pluck('id'))->sum('total');
        $discountInvoice = $totalInvoice * $data['discount'] / 100;
        $sumTotal = ($totalQuotation) - ($totalInvoice - $discountInvoice);
        if($sumTotal >= $total) {
            return null;
        }
        return $sumTotal;
    }

    /** Check Balance Invoice */
    public function checkBalanceInvoice($data)
    {
        $total = $data['amount'] * $data['price'];
        $invoice = Invoice::where('id', $data->id)->first();
        $invoices = Invoice::select('id')->where('quotation_id', $invoice['quotation_id']);
        $totalInvoice = InvoiceDetail::whereIn('invoice_id', $invoices)->sum('total');
        $totalQuotation = Quotation::where('id', $invoice['quotation_id'])->sum('total');
        $discountInvoice = $totalInvoice * $invoice['discount'] / 100;
        $sumTotal = ($totalQuotation) - ($totalInvoice - $discountInvoice);

        if($sumTotal >= $total) {
            return null;
        }
        return $sumTotal;
    }

    public function checkBalanceInvoiceByEdit($data)
    {
        $total = $data['amount'] * $data['price'];
        $detail = InvoiceDetail::where('id', $data['id'])->first();
        $invoice = Invoice::where('id', $detail['invoice_id'])->first();
        $totalQuotation = Quotation::where('id', $invoice['quotation_id'])->sum('total');
        $invoices = Invoice::select('id')->where('quotation_id', $invoice['quotation_id']);
        $totalInvoice = InvoiceDetail::whereIn('invoice_id', $invoices)->where('id', '!=', $detail['id'])->sum('total');
        $discountInvoice = $totalInvoice * $invoice['discount'] / 100;
        $sumTotal = ($totalQuotation) - ($totalInvoice - $discountInvoice);
        
        if($sumTotal >= $total) {
            return null;
        }
        return $sumTotal;
    }

    /** calculate invoice ທີ່ບໍ່ມີ id quotation */
    public function calculateInvoiceNoQuotationID($data)
    {
        $total = collect($data['invoice_details'])->sum(function ($detail) {
            return $detail['amount'] * $detail['price'];
        });

        $discountInvoice = $total * $data['discount'] / 100;
        $taxInvoice = $total * myHelper::TAX / 100;
        $sumTotal = ($total - $discountInvoice) + $taxInvoice;

        return $sumTotal;
    }

    /** edit calculate invoice ທີ່ບໍ່ມີ id quotation */
    public function calculateInvoiceNoQuotationID_ByEdit($invoice)
    {
        $sumSubTotal = InvoiceDetail::where('invoice_id', $invoice['id'])->get()->sum('total');
        $discountInvoice = $sumSubTotal * $invoice['discount'] / 100;
        $taxInvoice = $sumSubTotal * myHelper::TAX / 100;
        $sumTotal = ($sumSubTotal - $discountInvoice) + $taxInvoice;

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

     /** calculate Receipt */
    //  public function calculateTotalReceipt($request, $sumSubTotal, $id)
    //  {
    //      /** Calculate */
    //     $sumTotalTax = $sumSubTotal * myHelper::TAX / 100;
    //     $sumTotalDiscount = $sumSubTotal * $request['discount'] / 100;
    //     $sumTotal = ($sumSubTotal - $sumTotalDiscount) + $sumTotalTax;

    //     /** Update Total Receipt */
    //     $addQuotation = Receipt::find($id);
    //     $addQuotation->sub_total = $sumSubTotal;
    //     $addQuotation->total = $sumTotal;
    //     $addQuotation->save();
    //  }

     /** edit calculate Receipt */
    //  public function calculateTotalReceipt_ByEdit($request)
    //  {
    //      /** Calculate */
    //      $sumSubTotalPrice = ReceiptDetail::where('receipt_id', $request['id'])->get()->sum('total');
    //      $sumTotalTax = $sumSubTotalPrice * myHelper::TAX / 100;
    //      $sumTotalDiscount = $sumSubTotalPrice * $request['discount'] / 100;
    //      $sumTotal = ($sumSubTotalPrice - $sumTotalDiscount) + $sumTotalTax;

    //      /** Update Total Receipt */
    //      $editQuotation = Receipt::find($request['id']);
    //      $editQuotation->sub_total = $sumSubTotalPrice;
    //      $editQuotation->total = $sumTotal;
    //      $editQuotation->save();
    //  }

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
