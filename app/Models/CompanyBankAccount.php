<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompanyBankAccount extends Model
{
    use HasFactory;
    use SoftDeletes;

    public function company_invoice_bank_accounts()
    {
        return $this->hasMany(CompanyInvoiceBankAccount::class);
    }
}
