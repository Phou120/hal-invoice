<?php

namespace App\Services\GetData;

use App\Models\Invoice;
use App\Models\Quotation;
use App\Traits\ResponseAPI;
use App\Models\InvoiceDetail;
use App\Models\QuotationDetail;
use Illuminate\Support\Facades\Auth;

class GetDataOfQuotationService
{
    use ResponseAPI;

    /** get of quotation */
    public function getData($editQuotation, $request)
    {
        $editQuotation->quotation_name = $request['quotation_name'];
        $editQuotation->start_date = $request['start_date'];
        $editQuotation->end_date = $request['end_date'];
        $editQuotation->note = $request['note'];
        $editQuotation->quotation_type_id = $request['quotation_type_id'];
        $editQuotation->customer_id = $request['customer_id'];
        $editQuotation->updated_by = Auth::user('api')->id;
        $editQuotation->save();

        return $editQuotation;
    }

    /** update updated_by in quotation */
    public function getDataQuotation($quotationDetail)
    {
        $quotation = Quotation::find($quotationDetail->quotation_id);
        $quotation->updated_by = Auth::user('api')->id;
        $quotation->save();
    }

    /** update updated_by invoice */
    public function getDataInvoice($invoiceDetail)
    {
        $invoice = Invoice::find($invoiceDetail->invoice_id);
        $invoice->updated_by = Auth::user('api')->id;
        $invoice->save();
    }

}
