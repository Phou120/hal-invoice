<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Quotation extends Model
{
    use HasFactory;
    use SoftDeletes;


    public function format()
    {
        return [
            'id' => $this->id,
            'quotation_number' => $this->quotation_number,
            'quotation_name' => $this->quotation_name,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'note' => $this->note,
            'currencyName' => $this->currencyName,
            'currencyShortName' => $this->currencyShortName,
            'rate' => $this->rate,
            'rateSubTotal' => $this->rateSubTotal,
            'rateDiscount' => $this->rateDiscount,
            'rateTax' => $this->rateTax,
            'rateTotal' => $this->rateTotal,
            'count_details' => $this->count_details,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'quotation_type' => $this->quotation_type,
            'customer' => $this->customer,
            'currency' => $this->currency,
            'created_by' => $this->createdBy,
            'company' => $this->createdBy->company_user->company,
            'details' => $this->quotation_details
        ];
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function quotation_type() {
        return $this->belongsTo(QuotationType::class);
    }

    public function customer() {
        return $this->belongsTo(customer::class);
    }

    public function currency() {
        return $this->belongsTo(currency::class);
    }

    public function createdBy() {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function quotation_details()
    {
        return $this->hasMany(QuotationDetail::class);
    }
}
