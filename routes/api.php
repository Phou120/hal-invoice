<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\Company\CompanyController;
use App\Http\Controllers\Invoice\InvoiceController;
use App\Http\Controllers\Receipt\ReceiptController;
use App\Http\Controllers\Currency\CurrencyController;
use App\Http\Controllers\Customer\CustomerController;
use App\Http\Controllers\Quotation\QuotationController;
use App\Http\Controllers\PurchaseOrder\PurchaseOrderController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::group([
    'middleware' => 'api',
    'prefix' => 'auth'
], function ($router) {

    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout']);

});



Route::group([
    'middleware' => [
        'auth.jwt',
    ],
    'role:superAdmin|admin',
    'prefix' => 'admin',

], function() {

    /** CRUD Customers */
    Route::post('add-customer', [CustomerController::class, 'addCustomer'])->name('add.customer');
    Route::get('list-customers', [CustomerController::class, 'listCustomers']);
    Route::post('edit-customer/{id}', [CustomerController::class, 'editCustomer'])->name('edit.customer');
    Route::delete('delete-customer/{id}', [CustomerController::class, 'deleteCustomer'])->name('delete.customer');


    /** CRUD Currency */
    Route::post('add-currency', [CurrencyController::class, 'addCurrency'])->name('add.currency');
    Route::get('list-currencies', [CurrencyController::class, 'listCurrency']);
    Route::put('edit-currency/{id}', [CurrencyController::class, 'editCurrency'])->name('edit.currency');
    Route::delete('delete-currency/{id}', [CurrencyController::class, 'deleteCurrency'])->name('delete.currency');


    /** CRUD Company */
    Route::post('add-company', [CompanyController::class, 'addCompany'])->name('add.company');
    Route::get('list-companies', [CompanyController::class, 'listCompanies']);
    Route::post('edit-company/{id}', [CompanyController::class, 'editCompany'])->name('edit.company');
    Route::delete('delete-company/{id}', [CompanyController::class, 'deleteCompany'])->name('delete.company');


    /** CRUD Quotation and CRUD Quotation Detail */
    Route::post('add-quotation', [QuotationController::class, 'addQuotation'])->name('add.quotation');
    Route::get('list-quotations', [QuotationController::class, 'listQuotations']);
    Route::put('edit-quotation/{id}', [QuotationController::class, 'editQuotation'])->name('edit.quotation');
    Route::delete('delete-quotation/{id}', [QuotationController::class, 'deleteQuotation'])->name('delete.quotation');

    /** list-quotation-detail/{id} = {id} = ແມ່ນ id quotation  *** And ***  add-quotation-detail/{id} = {id} = ແມ່ນ id quotation */
    Route::post('add-quotation-detail/{id}', [QuotationController::class, 'addQuotationDetail'])->name('add.quotation.detail');
    Route::get('list-quotation-details/{id}', [QuotationController::class, 'listQuotationDetail'])->name('list.quotation.detail');

    /** edit-quotation-detail/{id} = {id} = ແມ່ນແກ້ໄຂ id detail  *** And ***  delete-quotation-detail/{id} = {id} = ແມ່ນລຶບ id detail */
    Route::put('edit-quotation-detail/{id}', [QuotationController::class, 'editQuotationDetail'])->name('edit.quotation.detail');
    Route::delete('delete-quotation-detail/{id}', [QuotationController::class, 'deleteQuotationDetail'])->name('delete.quotation.detail');


    /** CRUD Invoice and CRUD InvoiceDetail */
    Route::post('add-invoice', [InvoiceController::class, 'addInvoice'])->name('add.invoice');
    Route::get('list-invoices', [InvoiceController::class, 'listInvoices']);
    Route::put('edit-invoice/{id}', [InvoiceController::class, 'editInvoice'])->name('edit.invoice');
    Route::delete('delete-invoice/{id}', [InvoiceController::class, 'deleteInvoice'])->name('delete.invoice');

    /** list-invoice-detail/{id} = {id} = ແມ່ນ id invoice  *** And ***  add-invoice-detail/{id} = {id} = ແມ່ນ id invoice */
    Route::post('add-invoice-detail/{id}', [InvoiceController::class, 'addInvoiceDetail'])->name('add.invoice.detail');
    Route::get('list-invoice-detail/{id}', [InvoiceController::class, 'listInvoiceDetail'])->name('list.invoice.detail');

    /** edit-invoice-detail/{id} = {id} = ແມ່ນແກ້ໄຂ id detail  *** And ***  delete-invoice-detail/{id} = {id} = ແມ່ນລຶບ id detail */
    Route::put('edit-invoice-detail/{id}', [InvoiceController::class, 'editInvoiceDetail'])->name('edit.invoice.detail');
    Route::delete('delete-invoice-detail/{id}', [InvoiceController::class, 'deleteInvoiceDetail'])->name('delete.invoice.detail');

    /** update status in table Invoice */
    Route::put('update-invoice-status/{id}', [InvoiceController::class, 'updateInvoiceStatus'])->name('update.invoice.status');


    /** CRUD Receipt */
    Route::post('add-receipt', [ReceiptController::class, 'addReceipt'])->name('add.receipt');
    Route::put('edit-receipt/{id}', [ReceiptController::class, 'editReceipt'])->name('edit.receipt');
    Route::delete('delete-receipt/{id}', [ReceiptController::class, 'deleteReceipt'])->name('delete.receipt');
    Route::get('list-receipts', [ReceiptController::class, 'listReceipts']);

    /** list-receipt-detail/{id} => {id} = ແມ່ນ id receipt  *** And ***  add-receipt-detail/{id} => {id} = ແມ່ນ id receipt */
    Route::post('add-receipt-detail/{id}', [ReceiptController::class, 'addReceiptDetail'])->name('add.receipt.detail');
    Route::get('list-receipt-detail/{id}', [ReceiptController::class, 'listReceiptDetail'])->name('list.receipt.detail');

    /** edit-receipt-detail/{id} => {id} = ແກ້ ໄຂ id detail  *** And ***  delete-receipt-detail/{id} => {id} = ແມ່ນ ລຶບ id detail */
    Route::put('edit-receipt-detail/{id}', [ReceiptController::class, 'editReceiptDetail'])->name('edit.receipt.detail');
    Route::delete('delete-receipt-detail/{id}', [ReceiptController::class, 'deleteReceiptDetail'])->name('delete.receipt.detail');


    /** CRUD Purchaser Order */
    Route::post('add-purchase-order', [PurchaseOrderController::class, 'addPurchaseOrder'])->name('add.purchase.order');
    Route::put('edit-purchase-order/{id}', [PurchaseOrderController::class, 'editPurchaseOrder'])->name('edit.purchase.order');
    Route::delete('delete-purchase-order/{id}', [PurchaseOrderController::class, 'deletePurchaseOrder'])->name('delete.purchase.order');
    Route::get('list-purchase-orders', [PurchaseOrderController::class, 'listPurchaseOrders']);

    /** list-purchase-detail/{id} => {id} = ແມ່ນ id purchase  *** And ***  add-purchase-detail/{id} = {id} = ແມ່ນ id purchase */
    Route::post('add-purchase-detail/{id}', [PurchaseOrderController::class, 'addPurchaseDetail'])->name('add.purchase.detail');
    Route::get('list-purchase-detail/{id}', [PurchaseOrderController::class, 'listPurchaseDetail'])->name('list.purchase.detail');

    /** edit-purchase-detail/{id} => {id} = ແກ້ ໄຂ id detail  *** And ***  delete-purchase-detail/{id} => {id} = ແມ່ນ ລຶບ id detail */
    Route::put('edit-purchase-detail/{id}', [PurchaseOrderController::class, 'editPurchaseDetail'])->name('edit.purchase.detail');
    Route::delete('delete-purchase-detail/{id}', [PurchaseOrderController::class, 'deletePurchaseDetail'])->name('delete.purchase.detail');

    /** CRUD of User */
    Route::post('add-user', [UserController::class, 'addUser'])->name('add.user');
    Route::get('list-users', [UserController::class, 'listUser']);
    Route::put('edit-user/{id}', [UserController::class, 'editUser'])->name('edit.user');
    Route::delete('delete-user/{id}', [UserController::class, 'deleteUser'])->name('delete.user');

    /** change password */
    Route::put('change-password/{id}', [UserController::class, 'changePassword'])->name('change.password');

});

