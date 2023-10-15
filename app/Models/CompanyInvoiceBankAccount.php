<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompanyInvoiceBankAccount extends Model
{
    use HasFactory;
    use SoftDeletes;

    public function invoices()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function company_bank_account()
    {
        return $this->belongsTo(CompanyBankAccount::class);
    }
}
