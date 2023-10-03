<?php

namespace App\Services\returnData;

use App\Models\User;
use App\Models\Company;
use App\Models\Currency;
use App\Traits\ResponseAPI;
use App\Helpers\TableHelper;
use App\Helpers\filterHelper;
use App\Models\Invoice;
use App\Models\InvoiceDetail;
use App\Models\QuotationRate;
use App\Models\QuotationDetail;
use Illuminate\Support\Facades\DB;

class ReturnService
{
    use ResponseAPI;

    public function countUserCompany($invoiceQuery)
    {
        return $invoiceQuery->select(
            DB::raw('(SELECT COUNT(id) FROM companies) as company_count'),
            DB::raw('(SELECT COUNT(id) FROM customers) as customer_count')
        )->first();
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

    /** select quotationRate */
    public function selectQuotationRate($quotationDetailId)
    {
        $getQuotationRate = DB::table('quotation_rates')
            ->select('quotation_rates.*')
            ->join('quotations', 'quotation_rates.quotation_id', '=', 'quotations.id')
            ->join('quotation_details', 'quotation_details.quotation_id', '=', 'quotations.id')
            ->where('quotation_details.id', $quotationDetailId) // Use $getQuotation->id
            ->first();

        return $getQuotationRate;
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

    /** add quotation_details */
    // public function detailData($request)
    // {
    //     $data = [
    //         'order' => $request['order'],
    //         'quotation_id' => $request['id'],
    //         'name' => $request['name'],
    //         'hour' => $request['hour'],
    //         'description' => $request['description'],
    //         'created_at' => now(),
    //         'updated_at' => now(),
    //     ];

    //     return $data;
    // }

    /** join data in quotation_type to get quotation_types.rate */
    public function getQuotationRate($request)
    {
        $getQuotationType = DB::table('quotation_rates')
            ->select('quotation_rates.rate')
            ->join('quotations as quotation', 'quotation_rates.quotation_id', '=', 'quotation.id')
            ->join('quotation_details as quotationDetail', 'quotationDetail.quotation_id', '=', 'quotation.id')
            ->where('quotationDetail.quotation_id', '=', $request['id'])
            // ->whereNotNull('quotation.quotation_type_id')
            ->whereNotNull('quotation_rates.rate')
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

    // /** return data in company and customer */
    // public function returnData($company, $customer)
    // {
    //     return[
    //         'customer' => $customer,
    //         'company' => $company
    //     ];
    // }

    /** update type_quotation in invoice */
    public function updateTypeQuotationInInvoice($getQuotation, $addInvoice)
    {
        if($getQuotation){
            $addInvoice->type_quotation = 1;
            $addInvoice->save();
        }
    }

    /** map data in quotation */
    public function mapDataInQuotation($listQuotations)
    {
        $listQuotations->map(function ($item) {
            TableHelper::loopDataInQuotation($item);
        });

        return $listQuotations;
    }

    /** check role in quotation */
    public function checkRole($detailsQuery, $user)
    {
        if ($user->hasRole(['superadmin', 'admin'])) {
            $detailsQuery->orderBy('quotations.id', 'asc');
        } elseif ($user->hasRole(['company-admin', 'company-user'])) {
            $detailsQuery->where('quotations.created_by', $user->id)
                ->orderBy('quotations.id', 'asc');
        }
    }

    /** check role in Invoice */
    public function checkRoleInvoice($detailsQuery, $user)
    {
        if ($user->hasRole(['superadmin', 'admin'])) {
            $detailsQuery->orderBy('invoices.id', 'asc');
        } elseif ($user->hasRole(['company-admin', 'company-user'])) {
            $detailsQuery->where('invoices.created_by', $user->id)
                ->orderBy('invoices.id', 'asc');
        }
    }

    /** query quotationDetail */
    public function detailsQuery($request)
    {
        $detailsQuery = QuotationDetail::select('quotation_details.*')
            ->join('quotations', 'quotation_details.quotation_id', 'quotations.id')
            ->where('quotation_id', $request->id);

        return $detailsQuery;
    }

    /** query invoiceDetail */
    public function invoiceDetailsQuery($request)
    {
        $detailsQuery = InvoiceDetail::select('invoice_details.*')
            ->join('invoices', 'invoice_details.invoice_id', 'invoices.id')
            ->where('invoice_id', $request->id);

        return $detailsQuery;
    }

    /** sum rate and sum total in quotationRate */
    public function currencyTotals($quotationRates)
    {
        $currencyTotals = $quotationRates->groupBy('currency_id')->map(function ($group) {
            $currency = DB::table('currencies')->where('id', $group->first()->currency_id)->first();

            return [
                'currency' => $currency->short_name,
                'rate' => $group->sum('rate'),
                'total' => $group->sum('total'),
            ];
        });

        return $currencyTotals;
    }

    /** count quotation detail */
    public function countDetail($request)
    {
        $countDetail = DB::table('quotation_details')->whereNull('deleted_at')->where('quotation_id', $request->id)->count();

        return $countDetail;
    }

    /** count invoice detail */
    public function countDetailInvoice($request)
    {
        $countDetail = DB::table('invoice_details')->where('invoice_id', $request->id)->count();

        return $countDetail;
    }

    /** where quotation id */
    public function quotationRate($request)
    {
        $quotationRates = DB::table('quotation_rates')->where('quotation_id', $request->id)->get();

        return $quotationRates;
    }

    /** where quotation id */
    public function invoiceRate($request)
    {
        $invoiceRates = DB::table('invoice_rates')->where('invoice_id', $request->id)->get();

        return $invoiceRates;
    }
}
