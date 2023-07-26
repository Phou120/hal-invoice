<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
class myHelper
{
    const TAX = 7;
    const INVOICE_STATUS = [
        'CREATED' => 'created',
        'APPROVED' => 'approved',
        'INPROGRESS' => 'inprogress',
        'COMPLETED' => 'completed',
        'CANCELLED' => 'cancelled'
    ];
}
