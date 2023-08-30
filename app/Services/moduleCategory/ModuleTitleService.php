<?php

namespace App\Services\moduleCategory;

use App\Models\ModuleTitle;
use App\Traits\ResponseAPI;

class ModuleTitleService
{
    use ResponseAPI;

    public function createModuleTitle($request)
    {
        $createdTitle = new ModuleTitle();
        $createdTitle->module_category_id = $request['module_category_id'];
        $createdTitle->name = $request['name'];
        $createdTitle->hour = $request['hour'];
        $createdTitle->save();

        return response()->json([
            'error' => false,
            'msg' => 'ສຳເລັດແລ້ວ'
        ]);
    }

    public function listModuleTitle($request)
    {
        $perPage = $request['per_page'];

        $listTitle = ModuleTitle::select('module_titles.*')->orderBy('id', 'asc')->paginate($perPage);

        return response()->json([
            'module_title' => $listTitle
        ]);
    }

    public function updateModuleTitle($request)
    {
        $updateTitle = ModuleTitle::find($request['id']);
        $updateTitle->module_category_id = $request['module_category_id'];
        $updateTitle->name = $request['name'];
        $updateTitle->hour = $request['hour'];
        $updateTitle->save();

        return response()->json([
            'error' => false,
            'msg' => 'ສຳເລັດແລ້ວ'
        ]);
    }

    public function deleteModuleTitle($request)
    {
        $deleteTitle = ModuleTitle::find($request['id']);
        $deleteTitle->delete();

        return response()->json([
            'error' => false,
            'msg' => 'ສຳເລັດແລ້ວ'
        ]);
    }


    public function filterModuleTitleById($id)
    {
        $items = ModuleTitle::where('module_category_id', $id)->get();

        return response()->json([
            'module_titles' => $items
        ]);
    }
}
