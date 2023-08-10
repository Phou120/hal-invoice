<?php

namespace App\Http\Controllers\Currency;

use Illuminate\Http\Request;
use App\Services\CurrencyService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Currency\CurrencyRequest;

class CurrencyController extends Controller
{
    public $currencyService;

    public function __construct(CurrencyService $currencyService)
    {
        $this->currencyService = $currencyService;
    }


    public function addCurrency(CurrencyRequest $request)
    {
        return $this->currencyService->addCurrency($request);
    }

    public function listCurrency(Request $request)
    {
        return $this->currencyService->listCurrency($request);
    }

    public function editCurrency(CurrencyRequest $request)
    {
        return $this->currencyService->editCurrency($request);
    }

    public function deleteCurrency(CurrencyRequest $request)
    {
        return $this->currencyService->deleteCurrency($request);
    }
}
