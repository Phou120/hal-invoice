<?php

namespace App\Services\quotationType;

use App\Models\QuotationRate;
use App\Traits\ResponseAPI;

class QuotationRateService
{
    use ResponseAPI;

    public function listQuotationRates($request)
    {
        $perPage = $request->per_page;

        $query = QuotationRate::select('quotation_rates.*')->orderBy('id', 'desc')->paginate($perPage);

        return response()->json(['listQuotationRates' => $query]);
    }
}
