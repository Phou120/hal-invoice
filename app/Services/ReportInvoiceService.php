<?php

namespace App\Services;

use App\Traits\ResponseAPI;
use App\Helpers\filterHelper;
use Illuminate\Support\Facades\DB;

class ReportInvoiceService
{
    use ResponseAPI;

    public function reportInvoice($request)
    {
        $invoiceQuery = DB::table('invoices');
    // ->join('users', 'users.id', '=', 'invoices.created_by')
    // ->join('company_users', 'users.id', '=', 'company_users.user_id')
    // ->join('companies', 'company_users.company_id', '=', 'companies.id')
    // ->select('companies.id as company_id', DB::raw('count(*) as invoice_count'))
    // ->groupBy('companies.id')
    // ->get();

        /** filter start_date and end_date */
        $invoiceQuery = filterHelper::invoiceFilter($invoiceQuery, $request);

        $totalBill = (clone $invoiceQuery)->count(); // count all invoices

        $invoice = (clone $invoiceQuery)->orderBy('invoices.id', 'asc')->get();

        $invoice = filterHelper::getInvoicesStatus($invoice);

        $totalPrice = $invoice->sum('total'); // sum total of invoices all

        /** where status = created */
        $invoiceStatus = (clone $invoiceQuery)->where('status', filterHelper::INVOICE_STATUS['CREATED'])->orderBy('invoices.id', 'asc')->get();

        $invoiceStatus = filterHelper::getInvoicesStatus($invoiceStatus); // Apply transformation

        $created = (clone $invoiceStatus)->count(); // count status
        $createdTotal = (clone $invoiceStatus)->sum('total'); // sum total of invoices all

        /** where status = approved */
        $invoiceStatusApproved = (clone $invoiceQuery)->where('status', filterHelper::INVOICE_STATUS['APPROVED'])->orderBy('invoices.id', 'asc')->get();

        $invoiceStatusApproved = filterHelper::getInvoicesStatus($invoiceStatusApproved); // Apply transformation

        $approved = (clone $invoiceStatusApproved)->count(); // count status
        $approvedTotal = (clone $invoiceStatusApproved)->sum('total'); // sum total of invoices all

        /** where status = inprogress */
        $invoiceStatusInprogress = (clone $invoiceQuery)->where('status', filterHelper::INVOICE_STATUS['INPROGRESS'])->orderBy('invoices.id', 'asc')->get();

        $invoiceStatusInprogress = filterHelper::getInvoicesStatus($invoiceStatusInprogress); // Apply transformation

        $inprogress = (clone $invoiceStatusInprogress)->count(); // count status
        $inprogressTotal = (clone $invoiceStatusInprogress)->sum('total'); // sum total of invoices all

        /** where status = completed */
        $invoiceStatusCompleted = (clone $invoiceQuery)->where('status', filterHelper::INVOICE_STATUS['COMPLETED'])->orderBy('invoices.id', 'asc')->get();

        $invoiceStatusCompleted = filterHelper::getInvoicesStatus($invoiceStatusCompleted); // Apply transformation

        $completed = (clone $invoiceStatusCompleted)->count(); // count status
        $completedTotal = (clone $invoiceStatusCompleted)->sum('total'); // sum total of invoices all

        /** where status = canceled */
        $invoiceStatusCanceled = (clone $invoiceQuery)->where('status', filterHelper::INVOICE_STATUS['CANCELLED'])->orderBy('invoices.id', 'asc')->get();

        $invoiceStatusCanceled = filterHelper::getInvoicesStatus($invoiceStatusCanceled); // Apply transformation

        $canceled = (clone $invoiceStatusCanceled)->count(); // count status
        $canceledTotal = (clone $invoiceStatusCanceled)->sum('total'); // sum total of invoices all

        // $companyForInvoice = (clone $invoiceQuery)->select('created_by', DB::raw('count(*) as invoice_count'))
        // ->leftJoin('')
        // ->groupBy('company_id')
        // ->get();


        $invoice->transform(function ($item) {
            $companyForInvoice = DB::table('company_users')
            ->where('company_id', $item->id)
            ->sum('company_id');
            return $companyForInvoice;
        });

        $responseData = [
            'count_company' => $invoice,
            'totalPrice' => $totalPrice,
            'totalBill' => $totalBill,
            'created' => [
                'amount' => $created,
                'total' => $createdTotal
            ],
            'approved' => [
                'amount' => $approved,
                'total' => $approvedTotal,
             ],
            'inprogress' => [
               'amount' => $inprogress,
               'total' => $inprogressTotal,
            ],
            'completed' => [
                'amount' => $completed,
                'total' => $completedTotal,
            ],
            'canceled' => [
                'amount' => $canceled,
                'total' => $canceledTotal,
            ],
        ];

        return response()->json($responseData, 200);
    }
}
