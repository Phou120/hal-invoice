<?php

namespace App\Services\returnData;

use App\Models\User;
use App\Models\Company;
use App\Traits\ResponseAPI;
use App\Helpers\filterHelper;
use Illuminate\Support\Facades\DB;

class ReturnService
{
    use ResponseAPI;

    public function status($invoiceQuery)
    {
        $invoiceQuery = [
            'created' => FilterHelper::INVOICE_STATUS['CREATED'],
            'approved' => FilterHelper::INVOICE_STATUS['APPROVED'],
            'inprogress' => FilterHelper::INVOICE_STATUS['INPROGRESS'],
            'completed' => FilterHelper::INVOICE_STATUS['COMPLETED'],
            'cancelled' => FilterHelper::INVOICE_STATUS['CANCELLED'],
        ];

        return $invoiceQuery;
    }

    public function countUserCompany($invoiceQuery)
    {
        return $invoiceQuery->select(
            DB::raw('(SELECT COUNT(id) FROM companies) as company_count'),
            DB::raw('(SELECT COUNT(id) FROM customers) as customer_count')
        )->first();
    }

    public function outputData($foreach, $countUserCompany)
    {
        $output = [
            "company_count" => $countUserCompany->company_count,
            "customer_count" => $countUserCompany->customer_count,
        ] + $foreach;

        return $output;
    }

    public function responseData($quotationQuery)
    {
        $responseData = [
            'totalBill' => $quotationQuery->count(),
            'totalPrice' => 0,
        ];

        return $responseData;
    }

    public function selectQuotation($quotationDetailId)
    {
        $quotation = DB::table('quotations')
        ->select('quotation_detail.*')
        ->join('quotation_details as quotation_detail', 'quotation_detail.quotation_id', '=', 'quotations.id')
        ->where('quotation_detail.id', $quotationDetailId)
        ->first();

        return $quotation;
    }

    public function invoiceDetail($quotation, $invoiceId)
    {
        $invoiceDetails = [
            'description' => $quotation->description,
            'invoice_id' => $invoiceId,
            'amount' => $quotation->amount,
            'price' => $quotation->price,
            'order' => $quotation->order,
            'name' => $quotation->name,
            'total' => $quotation->amount * $quotation->price,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        return $invoiceDetails;
    }

    /** loop report quotations.status */
    public function foreach($statuses, $quotationQuery, $responseData)
    {
        foreach ($statuses as $status => $statusVariable) {
            $statusQuery = (clone $quotationQuery)->where('status', filterHelper::INVOICE_STATUS[$status])->orderBy('quotations.id', 'asc')->get();

            $statusCount = $statusQuery->count(); // count status
            $statusTotal = $statusQuery->sum('total'); // sum total of quotation all

            $responseData[$statusVariable] = [
                'count' => $statusCount,
                'total' => $statusTotal,
            ];
        }

        return $responseData;
    }

    /** loop quotation data */
    public function foreachData($statuses, $quotationQuery, $responseData)
    {
        foreach ($statuses as $statusName => $statusValue) {
            $statusQuery = clone $quotationQuery;
            $statusQuery->where('status', $statusValue);

            $statusQuotation = $statusQuery->orderBy('id', 'asc')->get();
            $statusQuotation = FilterHelper::getTotal($statusQuotation);

            $responseData[$statusName] = [
                'amount' => $statusQuotation->count(),
                'total' => $statusQuotation->sum('total'),
            ];

            $responseData['totalPrice'] += $responseData[$statusName]['total'];
        }

        return $responseData;
    }

    /** join data */
    public function joinData($request)
    {
        $editDetail = DB::table('quotation_details')
        ->select('quotation_details.*', 'quotation_types.rate', 'quotations.quotation_type_id')
        ->join('quotations', 'quotation_details.quotation_id', '=', 'quotations.id')
        ->join('quotation_types', 'quotations.quotation_type_id', '=', 'quotation_types.id')
        ->where('quotation_details.id', $request['id'])
        ->first();

        return $editDetail;
    }

    /** update data in quotation_detail */
    public function updateData($editDetail, $request)
    {
         // Update the fields
         $updateData = [
            'order' => $request['order'],
            'name' => $request['name'],
            'hour' => $request['hour'],
            'description' => $request['description'],
            'rate' => $editDetail->rate,
            'total' => $request['hour'] * $editDetail->rate
        ];

        return $updateData;
    }

    /** add quotation_details */
    public function detailData($request)
    {
        $data = [
            'order' => $request['order'],
            'quotation_id' => $request['id'],
            'name' => $request['name'],
            'hour' => $request['hour'],
            'description' => $request['description'],
            'created_at' => now(),
            'updated_at' => now(),
        ];

        return $data;
    }

    /** join data in quotation_type to get quotation_types.rate */
    public function getQuotationType($request)
    {
        $getQuotationType = DB::table('quotation_types')
            ->select('quotation_types.rate')
            ->join('quotations as quotation', 'quotation.quotation_type_id', '=', 'quotation_types.id')
            ->join('quotation_details as quotationDetail', 'quotationDetail.quotation_id', '=', 'quotation.id')
            ->where('quotationDetail.quotation_id', '=', $request['id'])
            ->whereNotNull('quotation.quotation_type_id')
            ->whereNotNull('quotation_types.rate')
            ->first();

        return $getQuotationType;
    }

    /** return user data */
    public function returnUserData($listUser, $roleUser, $permissionRole)
    {
        return [
            'user' => [
                'id' => $listUser->id,
                'name' => $listUser->name,
                'email' => $listUser->email,
                'profile_url' => $listUser->profile_url,
                'tel' => $listUser->tel,
                'created_at' => $listUser->created_at,
                'updated_at' => $listUser->updated_at,
                'roleUser' => $roleUser,
                'permissionRole' => $permissionRole,
            ]
        ];
    }

    /** return receipt data */
    public function returnDataReceipt($totalBill, $totalPrice, $listReceipt)
    {
        return [
            'totalBill' => $totalBill,
            'totalPrice' => $totalPrice,
            'listReceipt' => $listReceipt
        ];
    }

    /** return receipt */
    public function returnReceipt($totalReceipt, $totalPrice, $receipt)
    {
        return [
            'totalReceipt' => $totalReceipt,
            'totalPrice' => $totalPrice,
            'query' => $receipt
        ];
    }

    /** return data in company and customer */
    public function returnData($company, $customer)
    {
        return[
            'customer' => $customer,
            'company' => $company
        ];
    }

    /** response invoice data */
    public function responseInvoiceData(
        $totalBill, $totalPrice, $created, $createdTotal, $approved, $approvedTotal,
        $inprogress, $inprogressTotal, $completed, $completedTotal, $canceled, $canceledTotal, $listInvoice
    )
    {
        return [
            'totalBill' => $totalBill,
            'totalPrice' => $totalPrice,
            'created' => [
                'amount' => $created,
                'total' => $createdTotal,
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
             'cancelled' => [
                'amount' => $canceled,
                'total' => $canceledTotal,
             ],
            'listInvoice' => $listInvoice
        ];
    }

    /** response report quotation data */
    public function response(
        $totalBill, $totalPrice,$created,
        $createdTotal, $approved, $approvedTotal,$inprogress, $inprogressTotal,
        $completed, $completedTotal, $cancelled,$cancelledTotal
    )
    {
        return [
            'totalBill' => $totalBill,
            'totalPrice' => $totalPrice,
            'created' => [
                'amount' => $created,
                'total' => $createdTotal,
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
             'cancelled' => [
                'amount' => $cancelled,
                'total' => $cancelledTotal,
             ],
        ];
    }

    /** return quotation data */
    public function QuotationData(
        $totalQuotation, $totalPrice, $created, $createdTotal,
        $approved, $approvedTotal,$inprogress, $inprogressTotal,
        $completed, $completedTotal, $cancelled,$cancelledTotal, $listQuotations
    )
    {
        return [
            'totalQuotation' => $totalQuotation,
            'totalPrice' => $totalPrice,
            'created' =>[
                'amount' => $created,
                'total' => $createdTotal
            ],
            'approved' =>[
                'amount' => $approved,
                'total' => $approvedTotal
            ],
            'inprogress' =>[
                'amount' => $inprogress,
                'total' => $inprogressTotal
            ],
            'completed' =>[
                'amount' => $completed,
                'total' => $completedTotal
            ],
            'cancelled' =>[
                'amount' => $cancelled,
                'total' => $cancelledTotal
            ],
            'listQuotations' => $listQuotations,
        ];
    }
}
