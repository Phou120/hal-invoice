<?php

namespace App\Services;

use App\Models\Invoice;;
use App\Models\Quotation;
use App\Traits\ResponseAPI;
use App\Helpers\filterHelper;
use App\Models\InvoiceDetail;
use App\Models\PurchaseOrder;;
use App\Models\PurchaseDetail;
use App\Models\QuotationDetail;

class CalculateService
{
    use ResponseAPI;

    /** Sum Total invoice */
    public function sumTotalInvoice($taxRate, $discountRate, $sumSubTotal, $id)
    {
        /** Calculate */
        $sumTotalTax = $sumSubTotal * $taxRate / 100;
        $sumTotalDiscount = $sumSubTotal * $discountRate / 100;
        $sumTotal = $sumSubTotal - $sumTotalDiscount + $sumTotalTax;

        /** Update Total invoice */
        $addQuotation = Invoice::find($id);
        $addQuotation->sub_total = $sumSubTotal;
        $addQuotation->total = $sumTotal;
        $addQuotation->save();
    }

    // public function sumTotalQuotation($data)
    // {
        // $total = collect($data['invoice_details'])->sum(fn($detail) => $detail['amount'] * $detail['price']);

        // $quotation = Quotation::findOrFail($data['quotation_id']);
        // $totalQuotation = $quotation->total;

        // $invoiceIds = $quotation->invoices()->pluck('id');
        // $totalInvoice = InvoiceDetail::whereIn('invoice_id', $invoiceIds)->sum('total');

        // $discountInvoice = $totalInvoice * $data['discount'] / 100;

        // $sumTotal = $totalQuotation - ($totalInvoice - $discountInvoice);

        // return ($sumTotal >= $total) ? null : $sumTotal;
    //}

    /** Check Balance Invoice */
    public function checkBalanceInvoice($data)
    {
        // $total = $data['amount'] * $data['price'];

        // $invoice = Invoice::find($data['id']);
        // $invoiceId = $invoice->id;
        // $quotationId = $invoice->quotation_id;

        // $adjustedTotalInvoice = InvoiceDetail::whereIn('invoice_id', function ($query) use ($quotationId) {
        //     $query->select('id')->from('invoices')->where('quotation_id', $quotationId);
        // })->sum('total') * (1 - $invoice->discount / 100);

        // $remainingAmount = Quotation::where('id', $quotationId)->value('total') - $adjustedTotalInvoice;

        // return $remainingAmount >= $total ? null : $remainingAmount;

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

    /** get total in invoice */
    public function calculateTotalInvoice($invoiceDetail, $tax, $discount) {
        $calculateTax = $invoiceDetail * $tax / 100;
        $calculateDiscount = $invoiceDetail * $discount / 100;
        $sumTotal = ($invoiceDetail - $calculateDiscount) + $calculateTax;

        return $sumTotal;
    }

    /** Calculate invoice noQuotation */
    public function calculateInvoiceNoQuotation($request, $sumSubTotal, $id) {
        /** Calculate **/
        $sumTotalTax = $sumSubTotal * filterHelper::TAX / 100;
        $sumTotalDiscount = $sumSubTotal * $request['discount'] / 100;
        $sumTotal = ($sumSubTotal - $sumTotalDiscount) + $sumTotalTax;

        /** Update Total invoice */
        $addInvoice = Invoice::find($id);
        $addInvoice->tax = filterHelper::TAX;
        $addInvoice->sub_total = $sumSubTotal;
        $addInvoice->total = $sumTotal;
        $addInvoice->save();
     }

    /** calculate quotation */
    public function calculateTotal($request, $sumSubTotal, $id)
    {
        /** Calculate **/
        $sumTotalTax = $sumSubTotal * filterHelper::TAX / 100;
        $sumTotalDiscount = $sumSubTotal * $request['discount'] / 100;
        $sumTotal = ($sumSubTotal - $sumTotalDiscount) + $sumTotalTax;

        /** Update Total Quotation */
        $addQuotation = Quotation::find($id);
        $addQuotation->tax = filterHelper::TAX;
        $addQuotation->sub_total = $sumSubTotal;
        $addQuotation->total = $sumTotal;
        $addQuotation->save();
    }

    /** edit calculate quotation */
    public function calculateTotal_ByEdit($quotation)
    {
        /** Calculate */
        $sumSubTotalPrice = QuotationDetail::where('quotation_id', $quotation['id'])->get()->sum('total');
        $sumTotalTax = $sumSubTotalPrice * filterHelper::TAX / 100;
        $sumTotalDiscount = $sumSubTotalPrice * $quotation['discount'] / 100;
        $sumTotal = ($sumSubTotalPrice - $sumTotalDiscount) + $sumTotalTax;
        // dd($sumSubTotalPrice);

        /** Update Total Quotation */
        $editQuotation = Quotation::find($quotation['id']);
        $editQuotation->tax = filterHelper::TAX;
        $editQuotation->sub_total = $sumSubTotalPrice;
        $editQuotation->total = $sumTotal;
        $editQuotation->save();
    }

    /** calculate invoice */
    public function calculateTotalInvoice_ByEdit($editInvoice)
    {
        /** Calculate */
        $sumSubTotalPrice = InvoiceDetail::where('invoice_id', $editInvoice['id'])->get()->sum('total');
        $sumTotalTax = $sumSubTotalPrice * filterHelper::TAX / 100;
        $sumTotalDiscount = $sumSubTotalPrice * $editInvoice['discount'] / 100;
        $sumTotal = ($sumSubTotalPrice - $sumTotalDiscount) + $sumTotalTax;

        /** Update Total invoice */
        $editInvoice = Invoice::find($editInvoice['id']);
        $editInvoice->tax = filterHelper::TAX;
        $editInvoice->sub_total = $sumSubTotalPrice;
        $editInvoice->total = $sumTotal;
        $editInvoice->save();
    }

     /** calculate PurchaseOrder */
     public function calculateTotalOrder($request, $sumSubTotal, $id)
     {
         /** Calculate */
         $sumTotalTax = $sumSubTotal * filterHelper::TAX / 100;
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
         $sumTotalTax = $sumSubTotalPrice * filterHelper::TAX / 100;
         $sumTotalDiscount = $sumSubTotalPrice * $request['discount'] / 100;
         $sumTotal = ($sumSubTotalPrice - $sumTotalDiscount) + $sumTotalTax;

         /** Update Total PurchaseOrder */
         $editQuotation = PurchaseOrder::find($request['id']);
         $editQuotation->sub_total = $sumSubTotalPrice;
         $editQuotation->total = $sumTotal;
         $editQuotation->save();
     }
}
