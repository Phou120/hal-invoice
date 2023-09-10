<?php

namespace App\Models;

use App\Models\Quotation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Invoice extends Model
{
    use HasFactory;
    use SoftDeletes;
    public function format()
    {
        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'invoice_name' => $this->invoice_name,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'note' => $this->note,
            'sub_total' => $this->sub_total,
            'discount' => $this->discount,
            'tax' => $this->tax,
            'total' => $this->total,
            'count_details' => $this->count_details,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'quotation' => $this->quotation,
            'customer' => $this->customer,
            'currency' => $this->currency,
            'created_by' => $this->createdBy,
            'company' => $this->createdBy->company_user->company,
            'details' => $this->invoice_details
        ];
    }

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
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

    public function invoice_details()
    {
        return $this->hasMany(InvoiceDetail::class);
    }

}
