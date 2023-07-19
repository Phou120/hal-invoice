<?php

namespace App\Http\Controllers\Customer;

use Illuminate\Http\Request;
use App\Services\CustomerService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customers\CustomerRequest;

class CustomerController extends Controller
{
    public $customerService;

    public function __construct(CustomerService $customerService)
    {
        $this->customerService = $customerService;
    }


    public function addCustomer(CustomerRequest $request)
    {
        return $this->customerService->addCustomer($request);
    }

    public function listCustomers()
    {
        return $this->customerService->listCustomers();
    }

    public function editCustomer(CustomerRequest $request)
    {
        return $this->customerService->editCustomer($request);
    }

    public function deleteCustomer(CustomerRequest $request)
    {
        return $this->customerService->deleteCustomer($request);
    }
}
