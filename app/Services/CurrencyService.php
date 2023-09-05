<?php

namespace App\Services;

use App\Models\Currency;
use App\Traits\ResponseAPI;
use Illuminate\Support\Str;

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
            'error' => false,
            'msg' => 'ສຳເລັດແລ້ວ'
        ], 200);

    }

    /** list currency */
    public function listCurrency($request)
    {
        $perPage = $request->per_page;

        $listCurrency = Currency::orderBy('id', 'asc')->paginate($perPage);

        return response()->json([
            'listCurrency' => $listCurrency
        ], 200);
    }

    /** ແກ້ໄຂສະກຸນເງີນ */
    public function editCurrency($request)
    {
        $editCurrency = Currency::find($request['id']);
        $editCurrency->name = $request['name'];
        $editCurrency->short_name = $request['short_name'];
        $editCurrency->save();

        return response()->json([
            'error' => false,
            'msg' => 'ສຳເລັດແລ້ວ'
        ], 200);
    }

    /** ລຶບສະກຸນເງີນ */
    public function deleteCurrency($request)
    {
        $deleteCurrency = Currency::find($request['id']);
        $deleteCurrency->name = $deleteCurrency->name . '_deleted_' . Str::random(3);
        $deleteCurrency->short_name = $deleteCurrency->short_name . '_deleted_' . Str::random(3);
        $deleteCurrency->save();
        $deleteCurrency->delete();

        return response()->json([
            'error' => false,
            'msg' => 'ສຳເລັດແລ້ວ'
        ], 200);
    }
}
