<?php

namespace App\Services\quotationType;

use App\Traits\ResponseAPI;
use App\Models\QuotationRate;
use Illuminate\Support\Facades\Auth;

class QuotationRateService
{
    use ResponseAPI;

    // public function listQuotationRates($request)
    // {
    //     $user = Auth::user();
    //     $perPage = $request->per_page;

    //     $query = QuotationRate::select('quotation_rates.*')
    //     ->join('quotations', 'quotation_rates.quotation_id', 'quotations.id');

    //     if($user->hasRole(['superadmin', 'admin'])) {
    //         // Allow superadmin and admin to see all data
    //         $listQuotationRate = $query->orderBy('quotations.id', 'asc');

    //         /** do paginate */
    //         $getQuotationRate = $listQuotationRate->paginate($perPage);

    //         return response()->json($getQuotationRate, 200);
    //     }

    //     // check role company-admin and company-user based on user ID
    //     if($user->hasRole(['company-admin', 'company-user'])) {
    //         $listQuotationRate = $query->where('created_by', $user->id)->orderBy('quotations.id', 'asc');

    //         /** do paginate */
    //         $getQuotationRate = $listQuotationRate->paginate($perPage);

    //         return response()->json($getQuotationRate, 200);
    //     }
    // }
}
