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

        return response()->json([
            'success' => true,
            'msg' => 'ສຳເລັດແລ້ວ'
        ]);

    }

    /** list currency */
    public function listCurrency()
    {
        $listCurrency = Currency::select(
            'currencies.*'
        )
        ->orderBy('currencies.id', 'desc')->get();

        return response()->json([
            'listCurrency' => $listCurrency
        ]);
    }

    /** ແກ້ໄຂສະກຸນເງີນ */
    public function editCurrency($request)
    {
        $editCurrency = Currency::find($request['id']);
        $editCurrency->name = $request['name'];
        $editCurrency->short_name = $request['short_name'];
        $editCurrency->save();

        return response()->json([
            'success' => true,
            'msg' => 'ສຳເລັດແລ້ວ'
        ]);
    }

    /** ລຶບສະກຸນເງີນ */
    public function deleteCurrency($request)
    {
        $deleteCurrency = Currency::find($request['id']);
        $deleteCurrency->delete();

        return response()->json([
            'success' => true,
            'msg' => 'ສຳເລັດແລ້ວ'
        ]);
    }
}
