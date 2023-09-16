<?php

namespace App\Services\quotationType;

use App\Traits\ResponseAPI;
use App\Models\QuotationType;
use Illuminate\Support\Facades\DB;

class QuotationTypeService
{
    use ResponseAPI;

    public function createQuotationType($request)
    {
        $createType = new QuotationType();
        $createType->name = $request['name'];
        $createType->save();

        return response()->json([
            'error' => false,
            'msg' => 'ສຳເລັດແລ້ວ'
        ]);
    }

    public function listQuotationTypes($request)
    {
        $perPage = $request->per_page;

        $listType = DB::table('quotation_types')->orderBy('id', 'asc')->paginate($perPage);

        return response()->json([
            'quotation_type' => $listType
        ]);
    }

    public function updateQuotationType($request)
    {
        $updateType = QuotationType::find($request['id']);
        $updateType->name = $request['name'];
        $updateType->save();

        return response()->json([
            'error' => false,
            'msg' => 'ສຳເລັດແລ້ວ'
        ]);
    }

    public function deleteQuotationType($request)
    {
        $deleteType = QuotationType::find($request['id']);
        $deleteType->delete();

        return response()->json([
            'error' => false,
            'msg' => 'ສຳເລັດແລ້ວ'
        ]);
    }
}
