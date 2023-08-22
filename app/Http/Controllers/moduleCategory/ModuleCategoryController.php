<?php

namespace App\Http\Controllers\moduleCategory;

use App\Http\Controllers\Controller;
use App\Http\Requests\moduleCategory\ModuleCategoryRequest;
use App\Services\moduleCategory\ModuleCategoryService;
use Illuminate\Http\Request;

class ModuleCategoryController extends Controller
{
    public $moduleCategoryService;

    public function __construct(ModuleCategoryService $moduleCategoryService)
    {
        $this->moduleCategoryService = $moduleCategoryService;
    }

    public function createModuleCategory(ModuleCategoryRequest $request)
    {
        return $this->moduleCategoryService->createModuleCategory($request);
    }

    public function listModuleCategory(Request $request)
    {
        return $this->moduleCategoryService->listModuleCategory($request);
    }

    public function updateModuleCategory(ModuleCategoryRequest $request)
    {
        return $this->moduleCategoryService->updateModuleCategory($request);
    }

    public function deleteModuleCategory(ModuleCategoryRequest $request)
    {
        return $this->moduleCategoryService->deleteModuleCategory($request);
    }
}
