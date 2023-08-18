<?php

namespace App\Services\returnData;

use App\Models\User;
use App\Models\Company;
use App\Traits\ResponseAPI;
use App\Helpers\filterHelper;

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
            'canceled' => FilterHelper::INVOICE_STATUS['CANCELLED'],
        ];

        return $invoiceQuery;
    }

    public function responseData($quotationQuery)
    {
        $countUser = User::select('users.id')->count();
        $countCompany = Company::select('companies.id')->count();

        $responseData = [
            'totalUser' => $countUser,
            'totalCompany' => $countCompany,
            'totalBill' => $quotationQuery->count(),
            'totalPrice' => 0,
        ];

        return $responseData;
    }

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

    public function returnDataReceipt($totalBill, $totalPrice, $listReceipt)
    {
        return [
            'totalBill' => $totalBill,
            'totalPrice' => $totalPrice,
            'listReceipt' => $listReceipt
        ];
    }

    public function returnReceipt($totalReceipt, $totalPrice, $receipt)
    {
        return [
            'totalReceipt' => $totalReceipt,
            'totalPrice' => $totalPrice,
            'query' => $receipt
        ];
    }

    public function returnData($company, $customer)
    {
        return[
            'customer' => $customer,
            'company' => $company
        ];
    }

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
             'canceled' => [
                'amount' => $canceled,
                'total' => $canceledTotal,
             ],
            'listInvoice' => $listInvoice
        ];
    }

    public function response(
        $countCompany, $countUser, $totalBill, $totalPrice,$created,
        $createdTotal, $approved, $approvedTotal,$inprogress, $inprogressTotal,
        $completed, $completedTotal, $cancelled,$cancelledTotal
    )
    {
        return [
            'count_company' => $countCompany,
            'count_user' => $countUser,
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
             'canceled' => [
                'amount' => $cancelled,
                'total' => $cancelledTotal,
             ],
        ];
    }

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
