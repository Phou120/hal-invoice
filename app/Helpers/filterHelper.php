<?php

namespace App\Helpers;

use App\Models\User;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Quotation;
use App\Models\InvoiceDetail;
use App\Models\QuotationRate;
use App\Models\ReceiptDetail;
use App\Models\QuotationDetail;
use App\Services\CalculateService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class filterHelper
{
    const TAX = 7;
    const INVOICE_STATUS = [
        'CREATED' => 'created',
        'APPROVED' => 'approved',
        'INPROGRESS' => 'inprogress',
        'COMPLETED' => 'completed',
        'CANCELLED' => 'cancelled'
    ];

    /** filter of quotation */
    public static function filterStatus($quotations, $request)
    {
        if ($request->status !== null) {
            $quotations->where('quotations.status', $request->status);
        }

        return $quotations;
    }

    /** filter of invoice */
    public static function filterStatusOfInvoice($query, $request)
    {
        if ($request->status !== null) {
            $query->where('invoices.status', $request->status);
        }

        return $query;
    }

    /** filter invoice id */
    public static function filterIDInvoice($query, $request)
    {
        if ($request->id !== null) {
            $query->where('invoices.id', $request->id);
        }

        return $query;
    }

    /** filter quotation id  */
    public static function filterID($query, $request)
    {
        if ($request->id !== null) {
            $query->where('quotations.id', $request->id);
        }

        return $query;
    }

     /** filter invoice name */
     public static function filterInvoiceName($query, $request)
     {
         if ($request->name !== null) {
             $query->where('invoices.invoice_name', 'LIKE', '%' . $request->name . '%');
         }

         return $query;
     }

    /** filter quotation name */
    public static function filterQuotationName($listQuotation, $request)
    {
        if ($request->name !== null) {
            $listQuotation->where('quotations.quotation_name', 'LIKE', '%' . $request->name . '%');
        }

        return $listQuotation;
    }

    /** filter start_date and end_date */
    public static function quotationFilter($query, $request)
    {
        if($request->start_date && $request->end_date) {
            $query->whereRaw("DATE(quotations.start_date) BETWEEN ? AND ?", [$request->start_date, $request->end_date]);
        }

        return $query;
    }

    /** get data of invoiceDetail */
    public static function getTotal($invoiceStatus)
    {
        $invoiceStatus->transform(function($item) {
            $invoiceDetail = InvoiceDetail::where('invoice_id', $item->id)
                ->select(DB::raw("IFNULL(sum(total), 0) as total"))
                ->first()->total;

            $tax = $item->tax;
            $discount = $item->discount;

            $sumTotal = (new CalculateService())->calculateTotalInvoice($invoiceDetail, $tax, $discount);

            // Update the item with the calculated total
            $item->total = $sumTotal;

            return $item;
        });

        return $invoiceStatus;
    }

    /** sum total in receipt */
    public static function getReceipt($listReceipt)
    {
        $listReceipt->transform(function($item) {
            $receiptDetail = ReceiptDetail::where('receipt_id', $item->id)
                ->select(DB::raw("IFNULL(sum(total), 0) as total"))
                ->first()->total;

            $tax = $item->tax;
            $discount = $item->discount;

            $sumTotal = (new CalculateService())->calculateTotalInvoice($receiptDetail, $tax, $discount);

            // Update the item with the calculated total
            $item->total = $sumTotal;

            return $item;
        });

        return $listReceipt;
    }

    /** map data in invoice */
    // public static function mapDataInvoice($listInvoice)
    // {
    //     $listInvoice->transform(function($item) {
    //         $invoiceDetail = InvoiceDetail::where('invoice_id', $item['id'])
    //             ->select(DB::raw("IFNULL(sum(total), 0) as total"))->first()->total;

    //         $tax = $item['tax'];
    //         $discount = $item['discount'];

    //         $sumTotal = (new CalculateService())->calculateTotalInvoice($invoiceDetail, $tax, $discount);

    //         // Update the item with the calculated total
    //         $item['total'] = $sumTotal;

    //          /** loop data */
    //         TableHelper::formatDataInvoice($item);

    //         return $item;
    //     });

    //     return $listInvoice;
    // }


    // public static function mapDataReceipt($listReceipt)
    // {
    //     $listReceipt->transform(function($item) {
    //         $receiptDetail = ReceiptDetail::where('receipt_id', $item->id)
    //             ->select(DB::raw("IFNULL(sum(total), 0) as total"))
    //             ->first()->total;

    //         $tax = $item->tax;
    //         $discount = $item->discount;

    //         $sumTotal = (new CalculateService())->calculateTotalInvoice($receiptDetail, $tax, $discount);

    //         // Update the item with the calculated total
    //         $item->total = $sumTotal;

    //         TableHelper::loopDataOfReceipt($item);

    //         return $item;
    //     });

    //     return $listReceipt;
    // }


    public static function filterDate($query, $request)
    {
        if ($request->start_date && $request->end_date) {
            $query->whereRaw("DATE(invoices.start_date) BETWEEN ? AND ?", [$request->start_date, $request->end_date]);
        }

        return $query;
    }

    public static function receiptFilter($query, $request)
    {
        if ($request->receipt_date) {
            $query->whereRaw("DATE(receipts.receipt_date) = ?", [$request->receipt_date]);
        }

        return $query;
    }

    public static function filterName($query, $searchTerm)
    {
        if($searchTerm){
            $query->Where('users.name', 'like', '%' . $searchTerm . '%');
        }

        return $query;
    }


    public static function filterCustomerName($query, $searchTerm)
    {
        if ($searchTerm) {
            $query->where('customers.company_name', 'like', '%' . $searchTerm . '%');
        }

        return $query;
    }

    public static function filterCompanyName($query, $request)
    {
        if($request->search){
            $query->where(function ($item) use ($request) {
                $item->orWhere('companies.company_name', 'like', '%' . $request->search . '%');
            });
        }

        return $query;
    }

    public static function customerLogo($request)
    {
        $editCustomer = Customer::find($request['id']);
        $editCustomer->company_name = $request['company_name'];
        $editCustomer->phone = $request['phone'];
        $editCustomer->email = $request['email'];
        $editCustomer->address = $request['address'];
        $editCustomer->save();

        return $editCustomer;
    }

    public static function companyLogo($request)
    {
        $editCompany = Company::find($request['id']);
        $editCompany->company_name = $request['company_name'];
        $editCompany->phone = $request['phone'];
        $editCompany->email = $request['email'];
        $editCompany->address = $request['address'];
        $editCompany->save();

        return $editCompany;
    }

    public static function userProfile($request)
    {
        $editUser = User::find($request['id']);
        $editUser->name = $request['name'];
        $editUser->email = $request['email'];
        $editUser->tel = $request['tel'];
        $editUser->save();

        return $editUser;
    }

    /** update created_by in quotation */
    public static function updateCreatedByInQuotation($quotationDetail)
    {
        $quotation = Quotation::find($quotationDetail['quotation_id']);
        $quotation->updated_by = Auth::user('api')->id;
        $quotation->save();

        return $quotation;
    }

    /** update created_by in invoice */
    public static function updateCreatedByInInvoice($addDetail)
    {
        $invoice = Invoice::find($addDetail['invoice_id']);
        $invoice->updated_by = Auth::user('api')->id;
        $invoice->save();
    }

    public static function updateQuotationDetailStatusCreatedInvoice($deleteDetail)
    {
        $quotationDetail = QuotationDetail::find($deleteDetail->quotation_detail_id);

        if ($quotationDetail) {
            // Set the status_create_invoice to 0
            $quotationDetail->status_create_invoice = 0;
            $quotationDetail->save();
        }
    }
}
