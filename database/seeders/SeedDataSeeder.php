<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Quotation;
use Illuminate\Database\Seeder;
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
        $this->createCurrencies();
        $this->createCompanies();
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
            'name' => 'ໂດລາ',
            'short_name' => '$',
            'created_at' => '2023-06-30',
            'updated_at' => '2023-12-25'
        ]);

        Currency::create([
            'name' => 'ຢວນ',
            'short_name' => '¥',
            'created_at' => '2023-07-12',
            'updated_at' => '2023-12-25'
        ]);

        Currency::create([
            'name' => 'ໂດລາ',
            'short_name' => '$',
            'created_at' => '2023-01-30',
            'updated_at' => '2023-10-25'
        ]);

        Currency::create([
            'name' => 'ບາດ',
            'short_name' => '฿',
            'created_at' => '2023-03-31',
            'updated_at' => '2023-10-15'
        ]);

        Currency::create([
            'name' => 'ໂດລາ',
            'short_name' => '$',
            'created_at' => '2023-09-30',
            'updated_at' => '2023-10-25'
        ]);

        Currency::create([
            'name' => 'ກີບ',
            'short_name' => '₭',
            'created_at' => '2023-04-10',
            'updated_at' => '2023-10-25'
        ]);

        Currency::create([
            'name' => 'ຢວນ',
            'short_name' => '¥',
            'created_at' => '2023-08-12',
            'updated_at' => '2023-12-25'
        ]);

        Currency::create([
            'name' => 'ບາດ',
            'short_name' => '฿',
            'created_at' => '2023-08-31',
            'updated_at' => '2023-10-15'
        ]);

        Currency::create([
            'name' => 'ກີບ',
            'short_name' => '₭',
            'created_at' => '2023-08-10',
            'updated_at' => '2023-10-25'
        ]);

        Currency::create([
            'name' => 'ໂດລາ',
            'short_name' => '$',
            'created_at' => '2023-08-30',
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

    public function createQuotations()
    {
        // Quotation::create([
        //     'customer_id' => 1,
        //     'company_id' => 2,
        //     'currency_id' => 9,
        //     'created_by' => Auth::user('api')->id,
        //     'quotation_name' => 'ໃບສະເໝີລາຄາ ລະບົບສຳລັບຜູ້ໃຫ່ຍ',
        //     'start_date' => '2023-09-25',
        //     'end_date' => '2023-12-02',
        //     'note'
        // ]);
    }
}
