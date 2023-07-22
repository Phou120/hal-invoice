<?php

namespace App\Http\Controllers\Receipt;

use Illuminate\Http\Request;
use App\Services\ReceiptService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Receipt\ReceiptRequest;

class ReceiptController extends Controller
{
    public $receiptService;

    public function __construct(ReceiptService $receiptService)
    {
        $this->receiptService = $receiptService;
    }


    public function addReceipt(ReceiptRequest $request)
    {
        return $this->receiptService->addReceipt($request);
    }

    public function listReceipts()
    {
        return $this->receiptService->listReceipts();
    }

    public function addReceiptDetail(ReceiptRequest $request)
    {
        return $this->receiptService->addReceiptDetail($request);
    }

    public function listReceiptDetail(ReceiptRequest $request)
    {
        return $this->receiptService->listReceiptDetail($request);
    }

    public function editReceipt(ReceiptRequest $request)
    {
        return $this->receiptService->editReceipt($request);
    }

    public function editReceiptDetail(ReceiptRequest $request)
    {
        return $this->receiptService->editReceiptDetail($request);
    }

    public function deleteReceiptDetail(ReceiptRequest $request)
    {
        return $this->receiptService->deleteReceiptDetail($request);
    }

    public function deleteReceipt(ReceiptRequest $request)
    {
        return $this->receiptService->deleteReceipt($request);
    }

}
