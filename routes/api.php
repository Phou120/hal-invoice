<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ExportPDFController;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\report\ReportController;
use App\Http\Controllers\Company\CompanyController;
use App\Http\Controllers\Invoice\InvoiceController;
use App\Http\Controllers\Receipt\ReceiptController;
use App\Http\Controllers\User\UserProfileController;
use App\Http\Controllers\Currency\CurrencyController;
use App\Http\Controllers\Customer\CustomerController;
use App\Http\Controllers\Quotation\QuotationController;
use App\Http\Controllers\Invoice\ReportInvoiceController;
use App\Http\Controllers\CompanyUser\CompanyUserController;
use App\Http\Controllers\Quotation\QuotationTypeController;
use App\Http\Controllers\Company\CompanyBankAccountController;
use App\Http\Controllers\moduleCategory\ModuleTitleController;
use App\Http\Controllers\PurchaseOrder\PurchaseOrderController;
use App\Http\Controllers\Invoice\InvoiceNoQuotationIDController;
use App\Http\Controllers\moduleCategory\ModuleCategoryController;

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
    Route::post('refresh', [AuthController::class, 'refresh']);

});



Route::group([
    'middleware' => [
        'auth.jwt',
    ],
    'role:superadmin|admin|admin|company-admin|company-user',
    'prefix' => 'admin',

], function() {

    /** CRUD Customers */
    Route::post('add-customer', [CustomerController::class, 'addCustomer'])->name('add.customer')->middleware('role:superadmin|admin');
    Route::get('list-customers', [CustomerController::class, 'listCustomers'])->middleware('role:superadmin|admin');
    Route::put('edit-customer/{id}', [CustomerController::class, 'editCustomer'])->name('edit.customer')->middleware('role:superadmin|admin');
    Route::delete('delete-customer/{id}', [CustomerController::class, 'deleteCustomer'])->name('delete.customer')->middleware('role:superadmin|admin');

    /** list customer to use skip */
    Route::get('list-customer-skips', [CustomerController::class, 'listCustomersSkip'])->middleware('role:superadmin|admin');


    /** CRUD Currency */
    Route::post('add-currency', [CurrencyController::class, 'addCurrency'])->name('add.currency')->middleware('role:superadmin|admin');
    Route::get('list-currencies', [CurrencyController::class, 'listCurrency'])->middleware('role:superadmin|admin');
    Route::put('edit-currency/{id}', [CurrencyController::class, 'editCurrency'])->name('edit.currency')->middleware('role:superadmin|admin');
    Route::delete('delete-currency/{id}', [CurrencyController::class, 'deleteCurrency'])->name('delete.currency')->middleware('role:superadmin|admin');


    /** CRUD Company */
    Route::post('add-company', [CompanyController::class, 'addCompany'])->name('add.company')->middleware('role:superadmin|admin');
    Route::get('list-companies', [CompanyController::class, 'listCompanies'])->middleware('role:superadmin|admin');
    Route::put('edit-company/{id}', [CompanyController::class, 'editCompany'])->name('edit.company')->middleware('role:superadmin|admin');
    Route::delete('delete-company/{id}', [CompanyController::class, 'deleteCompany'])->name('delete.company')->middleware('role:superadmin|admin');

    /** list companies to use skip */
    Route::get('list-companies-skip', [CompanyController::class, 'listCompanyToUseSkip'])->middleware('role:superadmin|admin');


    /** CRUD Quotation and CRUD Quotation Detail */
    Route::post('add-quotation', [QuotationController::class, 'addQuotation'])->name('add.quotation')->middleware('role:company-admin|company-user');
    Route::get('list-quotations', [QuotationController::class, 'listQuotations']);
    Route::put('edit-quotation/{id}', [QuotationController::class, 'editQuotation'])->name('edit.quotation')->middleware('role:company-admin|company-user');
    Route::delete('delete-quotation/{id}', [QuotationController::class, 'deleteQuotation'])->name('delete.quotation')->middleware('role:company-admin|company-user');

    /** list-quotation-detail/{id} = {id} = ແມ່ນ id quotation  *** And ***  add-quotation-detail/{id} = {id} = ແມ່ນ id quotation */
    Route::post('add-quotation-detail/{id}', [QuotationController::class, 'addQuotationDetail'])->name('add.quotation.detail')->middleware('role:company-admin|company-user');
    Route::get('list-quotation-details/{id}', [QuotationController::class, 'listQuotationDetail'])->name('list.quotation.detail');

    /** edit-quotation-detail/{id} = {id} = ແມ່ນແກ້ໄຂ id detail  *** And ***  delete-quotation-detail/{id} = {id} = ແມ່ນລຶບ id detail */
    Route::put('edit-quotation-detail/{id}', [QuotationController::class, 'editQuotationDetail'])->name('edit.quotation.detail')->middleware('role:company-admin|company-user');
    Route::delete('delete-quotation-detail/{id}', [QuotationController::class, 'deleteQuotationDetail'])->name('delete.quotation.detail')->middleware('role:company-admin|company-user');

    /** update status quotation */
    Route::put('update-quotation-status/{id}', [QuotationController::class, 'updateQuotationStatus'])->name('update.quotation.status')->middleware('role:company-admin|company-user');
    /** update status in quotation_details */
    Route::put('update-detail-status/{id}', [QuotationController::class, 'updateDetailStatus'])->name('update.detail.status')->middleware('role:company-admin|company-user');


    /** CRUD Invoice */
    Route::post('add-invoice', [InvoiceController::class, 'addInvoice'])->name('add.invoice')->middleware('role:company-admin|company-user');
    Route::get('list-invoices', [InvoiceController::class, 'listInvoices']);
    Route::put('edit-invoice/{id}', [InvoiceController::class, 'editInvoice'])->name('edit.invoice')->middleware('role:company-admin|company-user');
    Route::delete('delete-invoice/{id}', [InvoiceController::class, 'deleteInvoice'])->name('delete.invoice')->middleware('role:company-admin|company-user');

    /** list-invoice-detail/{id} = {id} = ແມ່ນ id invoice  *** And ***  add-invoice-detail/{id} = {id} = ແມ່ນ id invoice */
    Route::post('add-invoice-detail/{id}', [InvoiceController::class, 'addInvoiceDetail'])->name('add.invoice.detail')->middleware('role:company-admin|company-user');
    Route::get('list-invoice-detail/{id}', [InvoiceController::class, 'listInvoiceDetail'])->name('list.invoice.detail');

    /** edit-invoice-detail/{id} = {id} = ແມ່ນແກ້ໄຂ id detail  *** And ***  delete-invoice-detail/{id} = {id} = ແມ່ນລຶບ id detail */
    //Route::put('edit-invoice-detail/{id}', [InvoiceController::class, 'editInvoiceDetail'])->name('edit.invoice.detail');
    Route::delete('delete-invoice-detail/{id}', [InvoiceController::class, 'deleteInvoiceDetail'])->name('delete.invoice.detail')->middleware('role:company-admin|company-user');

    /** update status in table Invoice */
    Route::put('update-invoice-status/{id}', [InvoiceController::class, 'updateInvoiceStatus'])->name('update.invoice.status')->middleware('role:company-admin|company-user');


    /** CURD invoice no quotation id */
    Route::post('add-invoice-no-quotation', [InvoiceNoQuotationIDController::class, 'addInvoiceNoQuotationID'])->name('add.invoice.no.quotation')->middleware('role:company-admin|company-user');
    Route::put('edit-invoice-no-quotation/{id}', [InvoiceNoQuotationIDController::class, 'editInvoiceNoQuotationID'])->name('edit.invoice.no.quotation')->middleware('role:company-admin|company-user');
    Route::post('add-invoice-no-quotation-detail/{id}', [InvoiceNoQuotationIDController::class, 'addInvoiceDetailNoQuotationID'])->name('add.invoice.no.quotation.detail')->middleware('role:company-admin|company-user');
    Route::put('edit-invoice-no-quotation-detail/{id}', [InvoiceNoQuotationIDController::class, 'editInvoiceDetailNoQuotationID'])->name('edit.invoice.no.quotation.detail')->middleware('role:company-admin|company-user');


    /** CRUD Receipt */
    Route::post('add-receipt', [ReceiptController::class, 'addReceipt'])->name('add.receipt')->middleware('role:company-admin|company-user');
    Route::get('list-receipts', [ReceiptController::class, 'listReceipts'])->middleware('role:superadmin|admin');
    Route::put('edit-receipt/{id}', [ReceiptController::class, 'editReceipt'])->name('edit.receipt')->middleware('role:company-admin|company-user');
    Route::delete('delete-receipt/{id}', [ReceiptController::class, 'deleteReceipt'])->name('delete.receipt')->middleware('role:company-admin|company-user');

    /** list-receipt-detail/{id} => {id} = ແມ່ນ id receipt  */
   // Route::get('list-receipt-detail/{id}', [ReceiptController::class, 'listReceiptDetail'])->name('list.receipt.detail')->middleware('role:superadmin|admin');

    /** delete-receipt-detail/{id} => {id} = ແມ່ນ ລຶບ id detail */
    // Route::delete('delete-receipt-detail/{id}', [ReceiptController::class, 'deleteReceiptDetail'])->name('delete.receipt.detail');


    /** CRUD Purchaser Order */
    Route::post('add-purchase-order', [PurchaseOrderController::class, 'addPurchaseOrder'])->name('add.purchase.order');
    Route::get('list-purchase-orders', [PurchaseOrderController::class, 'listPurchaseOrders']);
    Route::put('edit-purchase-order/{id}', [PurchaseOrderController::class, 'editPurchaseOrder'])->name('edit.purchase.order');
    Route::delete('delete-purchase-order/{id}', [PurchaseOrderController::class, 'deletePurchaseOrder'])->name('delete.purchase.order');

    /** list-purchase-detail/{id} => {id} = ແມ່ນ id purchase  *** And ***  add-purchase-detail/{id} = {id} = ແມ່ນ id purchase */
    Route::post('add-purchase-detail/{id}', [PurchaseOrderController::class, 'addPurchaseDetail'])->name('add.purchase.detail');
    Route::get('list-purchase-detail/{id}', [PurchaseOrderController::class, 'listPurchaseDetail'])->name('list.purchase.detail');

    /** edit-purchase-detail/{id} => {id} = ແກ້ ໄຂ id detail  *** And ***  delete-purchase-detail/{id} => {id} = ແມ່ນ ລຶບ id detail */
    Route::put('edit-purchase-detail/{id}', [PurchaseOrderController::class, 'editPurchaseDetail'])->name('edit.purchase.detail');
    Route::delete('delete-purchase-detail/{id}', [PurchaseOrderController::class, 'deletePurchaseDetail'])->name('delete.purchase.detail');

    /** CRUD of User */
    Route::post('add-user', [UserController::class, 'addUser'])->name('add.user')->middleware('role:superadmin|admin');
    Route::get('list-users', [UserController::class, 'listUsers'])->middleware('role:superadmin|admin');
    Route::put('edit-user/{id}', [UserController::class, 'editUser'])->name('edit.user')->middleware('role:superadmin|admin');
    Route::delete('delete-user/{id}', [UserController::class, 'deleteUser'])->name('delete.user')->middleware('role:superadmin|admin');

    /** change password */
    Route::put('change-password/{id}', [UserController::class, 'changePassword'])->name('change.password')->middleware('role:admin');

    /** Get User profile */
    Route::get('user-profile', [UserProfileController::class, 'ListUserProfile'])->middleware('role:superadmin|admin');

    /** CRUD CompanyUser */
    Route::get('list-company-users', [CompanyUserController::class, 'listCompanyUser'])->middleware('role:superadmin|admin');
    Route::post('create-company-user', [CompanyUserController::class, 'createCompanyUser'])->name('create.company.user')->middleware('role:company-admin|company-user');
    Route::put('update-company-user/{id}', [CompanyUserController::class, 'updateCompanyUser'])->name('update.company.user')->middleware('role:company-admin|company-user');
    Route::delete('delete-company-user/{id}', [CompanyUserController::class, 'deleteCompanyUser'])->name('delete.company.user')->middleware('role:company-admin|company-user');


    /** report invoices */
    Route::get('report-invoice', [ReportInvoiceController::class, 'reportInvoice'])->middleware('role:superadmin|admin');

    /** report quotation */
    Route::get('report-quotation', [ReportController::class, 'reportQuotation'])->middleware('role:superadmin|admin');

    /** report receipt */
    Route::get('report-receipt', [ReportController::class, 'reportReceipt'])->middleware('role:superadmin|admin');

    /** report company */
    Route::get('report-company-customer', [ReportController::class, 'reportCompanyCustomer'])->middleware('role:superadmin|admin');

    //Route::get('export-pdf', [ExportPDFController::class, 'exportPDF']);


    /** CRUD company_bank_account */
    Route::get('list-company-bank-accounts', [CompanyBankAccountController::class, 'listCompanyBankAccount'])->middleware('role:superadmin|admin');
    Route::post('create-company-bank-account', [CompanyBankAccountController::class, 'createCompanyBankAccount'])->name('create.bank.account')->middleware('role:superadmin|admin');
    Route::put('update-company-bank-account/{id}', [CompanyBankAccountController::class, 'updateCompanyBankAccount'])->name('update.bank.account')->middleware('role:superadmin|admin');
    Route::delete('delete-company-bank-account/{id}', [CompanyBankAccountController::class, 'deleteCompanyBankAccount'])->name('delete.bank.account')->middleware('role:superadmin|admin');

    /** update status */
    Route::put('update-status/{id}', [CompanyBankAccountController::class, 'updateStatus'])->name('update.status')->middleware('role:superadmin|admin');


    /** CRUD module categories */
    Route::get('list-module-categories', [ModuleCategoryController::class, 'listModuleCategory'])->middleware('role:superadmin|admin');
    Route::post('create-module-category', [ModuleCategoryController::class, 'createModuleCategory'])->name('create.module.category')->middleware('role:superadmin|admin');
    Route::put('update-module-category/{id}', [ModuleCategoryController::class, 'updateModuleCategory'])->name('update.module.category')->middleware('role:superadmin|admin');
    Route::delete('delete-module-category/{id}', [ModuleCategoryController::class, 'deleteModuleCategory'])->name('delete.module.category')->middleware('role:superadmin|admin');


    /** CRUD module title */
    Route::get('list-module-titles', [ModuleTitleController::class, 'listModuleTitle'])->middleware('role:superadmin|admin');
    Route::post('create-module-title', [ModuleTitleController::class, 'createModuleTitle'])->name('create.module.title')->middleware('role:superadmin|admin');
    Route::put('update-module-title/{id}', [ModuleTitleController::class, 'updateModuleTitle'])->name('update.module.title')->middleware('role:superadmin|admin');
    Route::delete('delete-module-title/{id}', [ModuleTitleController::class, 'deleteModuleTitle'])->name('delete.module.title')->middleware('role:superadmin|admin');


    /** CRUD quotation type */
    Route::get('list-quotation-types', [QuotationTypeController::class, 'listQuotationTypes'])->middleware('role:superadmin|admin');
    Route::post('create-quotation-type', [QuotationTypeController::class, 'createQuotationType'])->name('create.quotation.type')->middleware('role:superadmin|admin');
    Route::put('update-quotation-type/{id}', [QuotationTypeController::class, 'updateQuotationType'])->name('update.quotation.type')->middleware('role:superadmin|admin');
    Route::delete('delete-quotation-type/{id}', [QuotationTypeController::class, 'deleteQuotationType'])->name('delete.quotation.type')->middleware('role:superadmin|admin');


});


