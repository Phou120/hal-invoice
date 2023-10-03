<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Receipt extends Model
{
    use HasFactory;
    use SoftDeletes;

    public function format()
    {
        return [
            'id' => $this->id,
            'receipt_number' => $this->receipt_number,
            'invoice_rate_id' => $this->invoice_rate_id,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'receipt_name' => $this->receipt_name,
            'receipt_date' => $this->receipt_date,
            'note' => $this->note,
            'sub_total' => $this->subtotal,
            'discount' => $this->discount,
            'tax' => $this->tax,
            'total' => $this->total,
            'currencyName' => $this->currency_name,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at
        ];
    }
}
