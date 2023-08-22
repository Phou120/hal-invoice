<?php

namespace App\Services\moduleCategory;

use App\Traits\ResponseAPI;
use App\Models\ModuleCategory;

class ModuleCategoryService
{
    use ResponseAPI;

    public function createModuleCategory($request)
    {
        $create = new ModuleCategory();
        $create->name = $request['name'];
        $create->save();

        return response()->json([
            'error' => false,
            'msg' => 'ສຳເລັດແລ້ວ'
        ]);
    }

    public function listModuleCategory($request)
    {
        $perPage = $request->per_page;

        $listModule = ModuleCategory::select('module_categories.*')->orderBy('id', 'asc')->paginate($perPage);

        return response()->json([
            'module' => $listModule
        ]);
    }

    public function updateModuleCategory($request)
    {
        $updateModule = ModuleCategory::find($request['id']);
        $updateModule->name = $request['name'];
        $updateModule->save();

        return response()->json([
            'error' => false,
            'msg' => 'ສຳເລັດແລ້ວ'
        ]);
    }

    public function deleteModuleCategory($request)
    {
        $deleteModule = ModuleCategory::find($request['id']);
        $deleteModule->delete();

        return response()->json([
            'error' => false,
            'msg' => 'ສຳເລັດແລ້ວ'
        ]);
    }
}
