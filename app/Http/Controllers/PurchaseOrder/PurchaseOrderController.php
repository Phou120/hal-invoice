<?php

namespace App\Http\Controllers\PurchaseOrder;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\PurchaseOrder\PurchaseOrderService;
use App\Http\Requests\PurchaseOrder\PurchaseOrderRequest;

class PurchaseOrderController extends Controller
{
    public $purchaseOrderService;

    public function __construct(PurchaseOrderService $purchaseOrderService)
    {
        $this->purchaseOrderService = $purchaseOrderService;
    }


    public function addPurchaseOrder(PurchaseOrderRequest $request)
    {
        return $this->purchaseOrderService->addPurchaseOrder($request);
    }

    public function listPurchaseOrders()
    {
        return $this->purchaseOrderService->listPurchaseOrders();
    }

    public function addPurchaseDetail(PurchaseOrderRequest $request)
    {
        return $this->purchaseOrderService->addPurchaseDetail($request);
    }

    public function listPurchaseDetail(PurchaseOrderRequest $request)
    {
        return $this->purchaseOrderService->listPurchaseDetail($request);
    }

    public function editPurchaseOrder(PurchaseOrderRequest $request)
    {
        return $this->purchaseOrderService->editPurchaseOrder($request);
    }

    public function editPurchaseDetail(PurchaseOrderRequest $request)
    {
        return $this->purchaseOrderService->editPurchaseDetail($request);
    }

    public function deletePurchaseDetail(PurchaseOrderRequest $request)
    {
        return $this->purchaseOrderService->deletePurchaseDetail($request);
    }

    public function deletePurchaseOrder(PurchaseOrderRequest $request)
    {
        return $this->purchaseOrderService->deletePurchaseOrder($request);
    }
}
