<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvoiceRate extends Model
{
    use HasFactory;
    use SoftDeletes;

    public function invoices()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function currency() {
        return $this->belongsTo(currency::class);
    }
}
