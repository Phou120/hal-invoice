<?php

namespace App\Services\invoiceRate;

use App\Models\InvoiceRate;
use App\Traits\ResponseAPI;
use Illuminate\Support\Facades\Auth;

class InvoiceRateService
{
    use ResponseAPI;

    public function invoiceRates($request)
    {
        $user = Auth::user();
        $perPage = $request->per_page;

        $query = InvoiceRate::select('invoice_rates.*')
        ->join('invoices as invoice', 'invoice_rates.invoice_id', 'invoice.id');

        if($user->hasRole(['superadmin', 'admin'])) {
            // Allow superadmin and admin to see all data
            $listInvoiceRate = $query->orderBy('invoice.id', 'asc');

            /** do paginate */
            $getInvoiceRate = $listInvoiceRate->paginate($perPage);

            return response()->json($getInvoiceRate, 200);
        }

        if($user->hasRole(['company-admin', 'company-user'])) {
            // Filter invoices for company-admin and company-user based on user ID
            $listInvoiceRate = $query->where('created_by', $user->id)->orderBy('invoice.id', 'asc');

            /** do paginate */
            $getInvoiceRate = $listInvoiceRate->paginate($perPage);

            return response()->json($getInvoiceRate, 200);
        }
    }
}
