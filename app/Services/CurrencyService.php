<?php

namespace App\Services;

use App\Models\Currency;
use App\Traits\ResponseAPI;

class CurrencyService
{

    use ResponseAPI;

    /** add currency */
    public function addCurrency($request)
    {
        $addCurrency = new Currency();
        $addCurrency->name = $request['name'];
        $addCurrency->short_name = $request['short_name'];
        $addCurrency->save();

        return $addCurrency;

    }

    /** list currency */
    public function listCurrency()
    {
        $listCurrency = Currency::select(
            'currencies.*'
        )
        ->orderBy('currencies.id', 'desc')->get();

        return $listCurrency;
    }

    /** ແກ້ໄຂສະກຸນເງີນ */
    public function editCurrency($request)
    {
        $editCurrency = Currency::findOrFail($request['id']);
        $editCurrency->name = $request['name'];
        $editCurrency->short_name = $request['short_name'];
        $editCurrency->save();

        return $editCurrency;
    }

    /** ລຶບສະກຸນເງີນ */
    public function deleteCurrency($request)
    {
        $deleteCurrency = Currency::findOrFail($request['id']);
        $deleteCurrency->delete();

        return $deleteCurrency;
    }
}
