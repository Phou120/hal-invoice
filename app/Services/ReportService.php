<?php

namespace App\Services;

use App\Models\User;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Quotation;
use App\Traits\ResponseAPI;
use App\Helpers\TableHelper;
use App\Helpers\filterHelper;
use Illuminate\Support\Facades\DB;
use App\Services\filter\filterService;

class ReportService
{
    use ResponseAPI;

    public function reportInvoice($request)
    {
        $invoiceQuery = Invoice::query();

        // filters date
        $invoiceQuery = FilterHelper::filterDate($invoiceQuery, $request);

        // Define invoice statuses
        $statuses = (new filterService())->status($invoiceQuery);

        // Initialize data array
        $responseData = (new filterService())->responseData($invoiceQuery);

        /** foreach data */
        $foreach = (new filterService())->foreachData($statuses, $invoiceQuery, $responseData);

        return response()->json($foreach, 200);
    }

    public function reportQuotation($request)
    {
        $quotationQuery = Quotation::query();

        // filters date
        $quotationQuery = FilterHelper::quotationFilter($quotationQuery, $request);

        $totalBill = (clone $quotationQuery)->count(); // count all Quotations

        $quotation = (clone $quotationQuery)->orderBy('quotations.id', 'asc')->get();

        $totalPrice = $quotation->sum('total'); // sum all Quotations

        $quotationStatusCreated = (clone $quotationQuery)->where('status', filterHelper::INVOICE_STATUS['CREATED'])->orderBy('quotations.id', 'asc')->get();

        $created = (clone $quotationStatusCreated)->count(); // count status
        $createdTotal = (clone $quotationStatusCreated)->sum('total'); // sum total of quotation all


        $quotationStatusApproved = (clone $quotationQuery)->where('status', filterHelper::INVOICE_STATUS['APPROVED'])->orderBy('quotations.id', 'asc')->get();

        $approved = (clone $quotationStatusApproved)->count(); // count status
        $approvedTotal = (clone $quotationStatusApproved)->sum('total'); // sum total of quotation all


        $quotationStatusInprogress = (clone $quotationQuery)->where('status', filterHelper::INVOICE_STATUS['INPROGRESS'])->orderBy('quotations.id', 'asc')->get();

        $inprogress = (clone $quotationStatusInprogress)->count(); // count status
        $inprogressTotal = (clone $quotationStatusInprogress)->sum('total'); // sum total of quotation all


        $quotationStatusCompleted = (clone $quotationQuery)->where('status', filterHelper::INVOICE_STATUS['COMPLETED'])->orderBy('quotations.id', 'asc')->get();

        $completed = (clone $quotationStatusCompleted)->count(); // count status
        $completedTotal = (clone $quotationStatusCompleted)->sum('total'); // sum total of quotation all


        $quotationStatusCancelled = (clone $quotationQuery)->where('status', filterHelper::INVOICE_STATUS['CANCELLED'])->orderBy('quotations.id', 'asc')->get();

        $cancelled = (clone $quotationStatusCancelled)->count(); // count status
        $cancelledTotal = (clone $quotationStatusCancelled)->sum('total');

        $countCompany = TableHelper::countCompany($quotationQuery);
        $countUser = TableHelper::countUser($quotationQuery);

        $response = (new filterService())->response(
            $countCompany, $countUser, $totalBill, $totalPrice,$created,
            $createdTotal, $approved, $approvedTotal,$inprogress, $inprogressTotal,
            $completed, $completedTotal, $cancelled,$cancelledTotal
        );

        return response()->json($response, 200);
    }
}
