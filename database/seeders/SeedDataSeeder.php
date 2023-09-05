<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Quotation;
use App\Models\CompanyUser;
use App\Models\ModuleTitle;
use App\Models\QuotationType;
use App\Models\ModuleCategory;
use Illuminate\Database\Seeder;
use App\Models\CompanyBankAccount;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class SeedDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->createCustomers();
        $this->createCompanies();
        $this->createCurrencies();
        $this->createCompanyUsers();
        $this->createQuotationTypes();
        $this->createModuleCategories();
        $this->createModuleTitles();
        $this->createCompanyBankAccounts();
    }


    public function createCustomers()
    {
        Customer::create([
            'company_name' => 'halTech',
            'phone' => '55588887',
            'email' => 'halTech@gmail.com',
            'address' => 'ແສງສະຫວ່າງ',
            'logo' => 'company_logo789.png',
            'created_at' => '2023-08-30',
            'updated_at' => '2023-10-25'
        ]);

        Customer::create([
            'company_name' => 'Boun Zou ',
            'phone' => '55588887',
            'email' => 'BounZou@gmail.com',
            'address' => 'ບ້ານໂພນພະເນົາ',
            'logo' => 'company_logo459.png',
            'created_at' => '2023-08-30',
            'updated_at' => '2023-10-25'
        ]);

        Customer::create([
            'company_name' => 'VKK Industry',
            'phone' => '55588882',
            'email' => 'VKKIndustry@gmail.com',
            'address' => 'ບ້ານ ໂພນເຄັງ',
            'logo' => 'company_logo789.png',
            'created_at' => '2023-08-20',
            'updated_at' => '2023-10-25'
        ]);

        Customer::create([
            'company_name' => 'MGM IT',
            'phone' => '57588897',
            'email' => 'MGMIT@gmail.com',
            'address' => 'ບ້ານ ໂພນເຄັງ',
            'logo' => 'company_logo649.png',
            'created_at' => '2023-08-30',
            'updated_at' => '2023-10-25'
        ]);

        Customer::create([
            'company_name' => 'Laos IT',
            'phone' => '55589887',
            'email' => 'LaosIT@gmail.com',
            'address' => 'ວຽງຈັນ',
            'logo' => 'company_logo788.png',
            'created_at' => '2023-08-10',
            'updated_at' => '2023-10-22'
        ]);

        Customer::create([
            'company_name' => '108Job',
            'phone' => '55588889',
            'email' => '108Job@gmail.com',
            'address' => 'ນາບອນ',
            'logo' => 'company_logo765.png',
            'created_at' => '2023-08-31',
            'updated_at' => '2023-10-24'
        ]);
    }

    public function createCurrencies()
    {
        Currency::create([
            'name' => 'ໂດລາ',
            'short_name' => '$',
            'created_at' => '2023-08-30',
            'updated_at' => '2023-06-25'
        ]);

        Currency::create([
            'name' => 'ບາດ',
            'short_name' => '฿',
            'created_at' => '2023-03-31',
            'updated_at' => '2023-10-15'
        ]);

        Currency::create([
            'name' => 'ກີບ',
            'short_name' => '₭',
            'created_at' => '2023-04-10',
            'updated_at' => '2023-10-25'
        ]);
    }

    public function createCompanies()
    {
        Company::create([
            'company_name' => 'Unitel',
            'phone' => '99998887',
            'email' => 'Unitel@gmail.com',
            'address' => 'ນະຄອນຫຼວງວຽງຈັນ',
            'logo' => 'company_logo999.png',
            'created_at' => '2023-05-30',
            'updated_at' => '2023-04-02'
        ]);

        Company::create([
            'company_name' => 'ETL',
            'phone' => '22222333',
            'email' => 'ETL@gmail.com',
            'address' => 'ບ້ານຈອມມະນີ',
            'logo' => 'company_logo999.png',
            'created_at' => '2023-05-30',
            'updated_at' => '2023-04-02'
        ]);

        Company::create([
            'company_name' => 'Laos DEV',
            'phone' => '99998881',
            'email' => 'LaosDEV@gmail.com',
            'address' => 'ບ້ານໂພນຕ້ອງ',
            'logo' => 'company_logo999.png',
            'created_at' => '2023-01-30',
            'updated_at' => '2023-04-12'
        ]);

        Company::create([
            'company_name' => 'LOGO',
            'phone' => '99998880',
            'email' => 'LOGO@gmail.com',
            'address' => 'ບ້ານ ທົ່ງສ້າງນາງ',
            'logo' => 'company_logo999.png',
            'created_at' => '2023-09-10',
            'updated_at' => '2023-10-02'
        ]);

        Company::create([
            'company_name' => 'IT Laos',
            'phone' => '99998884',
            'email' => 'ITLaos@gmail.com',
            'address' => 'ບ້ານໂພນເຄັງ',
            'logo' => 'company_logo996.png',
            'created_at' => '2023-05-20',
            'updated_at' => '2023-07-12'
        ]);

        Company::create([
            'company_name' => 'Dev',
            'phone' => '99998885',
            'email' => 'Dev@gmail.com',
            'address' => 'ບ້ານໂພນພະເນົາ',
            'logo' => 'company_logo998.png',
            'created_at' => '2023-05-31',
            'updated_at' => '2023-08-09'
        ]);

        Company::create([
            'company_name' => 'Lao',
            'phone' => '99998888',
            'email' => 'Lao@gmail.com',
            'address' => 'ວຽງຈັນ',
            'logo' => 'company_logo989.png',
            'created_at' => '2023-09-23',
            'updated_at' => '2023-04-05'
        ]);
    }

    public function createCompanyBankAccounts()
    {
        CompanyBankAccount::create([
            'company_id' => 1,
            'bank_name' => 'JDB',
            'account_name' => 'halTech',
            'account_number' => '222455669872',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        CompanyBankAccount::create([
            'company_id' => 2,
            'bank_name' => 'JDB',
            'account_name' => 'dev',
            'account_number' => '222455669843',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        CompanyBankAccount::create([
            'company_id' => 3,
            'bank_name' => 'JDB',
            'account_name' => 'ພັດທະນາລາວ',
            'account_number' => '222455669889',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function createCompanyUsers()
    {
        CompanyUser::create([
            'company_id' => 1,
            'user_id' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        CompanyUser::create([
            'company_id' => 2,
            'user_id' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        CompanyUser::create([
            'company_id' => 3,
            'user_id' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function createModuleCategories()
    {
        ModuleCategory::create([
            'name' => 'hal',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        ModuleCategory::create([
            'name' => 'tech',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function createModuleTitles()
    {
        ModuleTitle::create([
            'module_category_id' => 1,
            'name' => 'for',
            'hour' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        ModuleTitle::create([
            'module_category_id' => 2,
            'name' => 'forget',
            'hour' => 5,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function createQuotationTypes()
    {
        QuotationType::create([
            'currency_id' => 1,
            'name' => 'big',
            'rate' => 200,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        QuotationType::create([
            'currency_id' => 2,
            'name' => 'big',
            'rate' => 300,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        QuotationType::create([
            'currency_id' => 3,
            'name' => 'big',
            'rate' => 210,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
