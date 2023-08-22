<?php

namespace App\Http\Controllers\moduleCategory;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\moduleCategory\ModuleTitleService;
use App\Http\Requests\moduleCategory\ModuleTitleRequest;

class ModuleTitleController extends Controller
{
    public $moduleTitleService;

    public function __construct(ModuleTitleService $moduleTitleService)
    {
        $this->moduleTitleService = $moduleTitleService;
    }

    public function createModuleTitle(ModuleTitleRequest $request)
    {
        return $this->moduleTitleService->createModuleTitle($request);
    }

    public function listModuleTitle(Request $request)
    {
        return $this->moduleTitleService->listModuleTitle($request);
    }

    public function updateModuleTitle(ModuleTitleRequest $request)
    {
        return $this->moduleTitleService->updateModuleTitle($request);
    }

    public function deleteModuleTitle(ModuleTitleRequest $request)
    {
        return $this->moduleTitleService->deleteModuleTitle($request);
    }
}
