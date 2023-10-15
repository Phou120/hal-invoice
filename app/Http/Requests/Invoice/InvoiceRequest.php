<?php

namespace App\Http\Requests\Invoice;

use App\Models\Invoice;
use App\Models\CompanyUser;
use App\Models\InvoiceDetail;
use App\Models\QuotationDetail;
use Illuminate\Validation\Rule;
use App\Models\CompanyBankAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Http\FormRequest;

class InvoiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth('api')->user()->hasRole('superadmin|admin|company-admin|company-user');
    }


    public function prepareForValidation()
    {
        if($this->isMethod('put') && $this->routeIs('edit.invoice')
             ||$this->isMethod('delete') && $this->routeIs('delete.invoice')
            //  ||$this->isMethod('put') && $this->routeIs('edit.invoice.detail')
             ||$this->isMethod('post') && $this->routeIs('add.invoice.detail')
             ||$this->isMethod('put') && $this->routeIs('update.invoice.status')
             ||$this->isMethod('delete') && $this->routeIs('delete.invoice.detail')
             ||$this->isMethod('get') && $this->routeIs('list.invoice.detail')

        ){
            $this->merge([
                'id' => $this->route()->parameters['id'],

            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        if($this->isMethod('get') && $this->routeIs('list.invoice.detail'))
        {
            return [
                'id' =>[
                    'required',
                        'numeric',
                            Rule::exists('invoices', 'id')
                                ->whereNull('deleted_at')
                ],
            ];
        }

        if($this->isMethod('put') && $this->routeIs('update.invoice.status'))
        {
            return [
                'id' =>[
                    'required',
                        'numeric',
                            Rule::exists('invoices', 'id')
                                ->whereNull('deleted_at')
                ],
                'status' =>[
                    'required',
                        Rule::in('created', 'approved', 'inprogress', 'completed', 'cancelled')
                ]
            ];
        }

        if($this->isMethod('delete') && $this->routeIs('delete.invoice'))
        {
            return [
                'id' =>[
                    'required',
                        'numeric',
                            Rule::exists('invoices', 'id')
                                ->where(function ($q){
                                    $q->whereNull('deleted_at');
                                }),
                                function($attribute, $value, $fail){
                                    $checkItem = Invoice::where('id', $value)->where('status', 'completed')->exists();
                                    if($checkItem){
                                        $fail('ບໍ່ສາມາດລຶບໃບເກັບເງິນນີ້ໄດ້ ເພາະວ່າຖືກອອກໃບເກັບເງິນແລ້ວ...');
                                    }
                                }
                ],
            ];
        }

        if($this->isMethod('delete') && $this->routeIs('delete.invoice.detail'))
        {
            return [
                'id' =>[
                    'required',
                        'numeric',
                            Rule::exists('invoice_details', 'id')
                                ->where(function ($query) {
                                    $query->whereNull('deleted_at');
                                }),
                                function($attribute, $value, $fail){
                                    $detail = InvoiceDetail::where('id', $value)->first(); // Retrieve the InvoiceDetail

                                    if ($detail) {
                                        $checkItem = Invoice::where('id', $detail->invoice_id)
                                            ->where('status', 'completed')
                                            ->exists();

                                        if ($checkItem) {
                                            $fail('ບໍ່ສາມາດລຶບລາຍລະອຽດໃບເກັບເງິນນີ້ໄດ້ ເພາະວ່າຖືກອອກໃບເກັບເງິນແລ້ວ...');
                                        }
                                    }
                                }
                ],
            ];
        }

        // if($this->isMethod('put') && $this->routeIs('edit.invoice.detail'))
        // {
        //     return [
        //         'id' =>[
        //             'required',
        //                 'numeric',
        //                     Rule::exists('invoice_details', 'id')
        //                         ->where(function ($query) {
        //                             $query->whereNull('deleted_at');
        //                         }),
        //                         function($attribute, $value, $fail){
        //                             $checkItem = Invoice::where('id', $value)->where('status', 'completed')->exists();
        //                             if($checkItem){
        //                                 $fail('ບໍ່ສາມາດແກ້ໄຂລາຍລະອຽດໃບເກັບເງິນນີ້ໄດ້ ເພາະວ່າຖືກອອກໃບເກັບເງິນແລ້ວ...');
        //                             }
        //                         }
        //         ],
        //         'order' => [
        //             'required',
        //                 'numeric'
        //         ],
        //         'name' => [
        //             'required',
        //                 'max:255'
        //         ],
        //         'amount' => [
        //             'required',
        //                 'numeric'
        //         ],
        //         'price' => [
        //             'required',
        //                 'numeric'
        //         ]
        //     ];
        // }

        if($this->isMethod('put') && $this->routeIs('edit.invoice'))
        {
            return [
                'id' =>[
                    'required',
                        'numeric',
                            Rule::exists('invoices', 'id')
                                ->where(function ($q){
                                    $q->whereNull('deleted_at');
                                }),
                            function($attribute, $value, $fail){
                                $checkItem = Invoice::where('id', $value)->where('status', 'completed')->exists();
                                if($checkItem){
                                    $fail('ບໍ່ສາມາດແກ້ໄຂໃບເກັບເງິນນີ້ໄດ້ ເພາະວ່າຖືກອອກໃບເກັບເງິນແລ້ວ...');
                                }
                            }
                ],
                'invoice_name' =>[
                    'required',
                        'max:255'
                ],
                'start_date' => [
                    'required',
                        'date',
                            'after_or_equal:today',
                ],
                'end_date' => [
                    'required',
                        'date',
                            'after_or_equal:start_date'
                ],
                // 'customer_id' => [
                //     'required',
                //         'numeric',
                //             Rule::exists('customers', 'id')
                //                 ->whereNull('deleted_at')
                // ],
                // 'currency_id' => [
                //     'required',
                //         'numeric',
                //             Rule::exists('currencies', 'id')
                //                 ->whereNull('deleted_at')
                // ]
            ];
        }

        if($this->isMethod('post') && $this->routeIs('add.invoice.detail'))
        {
            return [
                'id' =>[
                    'required',
                        'numeric',
                            Rule::exists('invoices', 'id')
                                ->whereNull('deleted_at')
                ],
                'quotation_detail_id' =>[
                    'required',
                        'array',
                            Rule::exists('quotation_details', 'id')->whereNull('deleted_at'),
                            function ($attribute, $value, $fail) {
                                foreach ($value as $quotation_detail_id) {
                                    $statusCreateInvoice = QuotationDetail::where('id', $quotation_detail_id)
                                    ->value('status_create_invoice');

                                    if ($statusCreateInvoice === 1) {
                                        $fail('ລາຍການນີ້ຖືກອອກໃບເກັບເງິນແລ້ວ ' .$quotation_detail_id);
                                    }
                                }
                            },
                            function ($attribute, $value, $fail) {
                                $quotationIds = collect($value)
                                ->map(fn($quotationDetailId) => optional(QuotationDetail::find($quotationDetailId))->quotation_id)
                                ->unique()
                                ->values();

                                if ($quotationIds->count() > 1) {
                                    $fail("ກະລຸນາເລືອກໃຫ້ຖືກຕ້ອງ...");
                                }
                            },
                            function ($attribute, $value, $fail) {
                                foreach ($value as $quotation_detail_id) {
                                    // Get the `quotation_id` from the QuotationDetail model
                                    $quotationId = QuotationDetail::where('id', $quotation_detail_id)->value('quotation_id');
                                    // Retrieve the invoice_id from the URL parameter
                                    $invoiceID = request()->route('id');

                                    // Check if the provided invoice_id matches the invoice with the corresponding quotation_id
                                    $invoice = Invoice::where('id', $invoiceID)->first();
                                    $whereQuotationID = $invoice->where('quotation_id', $quotationId)->first();

                                    if(!$whereQuotationID){
                                        $fail('id ບໍ່ມີໃນລະບົບ...');
                                    }
                                    $check = $invoice == $whereQuotationID;

                                    if (!$check) {
                                        $fail('ກະລຸນາເລືອກ ' . $quotation_detail_id . ' ໃຫ້ຖືກຕ້ອງ...');
                                    }
                                }
                            }
                            // }
                            // function ($attribute, $value, $fail) {
                            //     $quotationDetailIds = $value;

                            //     $invoiceID = request()->route('id');

                            //     $quotationIds = QuotationDetail::whereIn('id', $quotationDetailIds)->pluck('quotation_id');
                            //     // dd($quotationDetailIds);

                            //     $invoices = Invoice::where('id', $invoiceID)->whereIn('quotation_id', $quotationIds)->get();

                            //     if ($invoices->count() !== count($quotationDetailIds)) {
                            //         $fail('ກະລຸນາເລືອກລາຍລະອຽດທັງໝົດໃຫ້ຖືກຕ້ອງ...');
                            //     }
                            // }
                ],
                // 'order' => [
                //     'required',
                //         'numeric'
                // ],
            ];
        }

        if($this->isMethod('post') && $this->routeIs('add.invoice'))
        {
            return [
                'invoice_name' =>[
                    'required',
                        'max:255'
                ],
                'start_date' => [
                    'required',
                        'date',
                            'after_or_equal:today',
                ],
                'end_date' => [
                    'required',
                        'date',
                            'after_or_equal:start_date'
                ],
                'quotation_detail_id' =>[
                    'required',
                        'array',
                            Rule::exists('quotation_details', 'id')->whereNull('deleted_at'),
                            function ($attribute, $value, $fail) {
                                foreach ($value as $quotation_detail_id) {
                                    $statusCreateInvoice = QuotationDetail::where('id', $quotation_detail_id)
                                    ->value('status_create_invoice');

                                    if ($statusCreateInvoice === 1) {
                                        $fail('ລາຍການນີ້ຖືກອອກໃບເກັບເງິນແລ້ວ ' .$quotation_detail_id);
                                    }
                                }
                            },
                            function ($attribute, $value, $fail) {
                                $quotationIds = collect($value)
                                ->map(fn($quotationDetailId) => optional(QuotationDetail::find($quotationDetailId))->quotation_id)
                                ->unique()
                                ->values();

                                if ($quotationIds->count() > 1) {
                                    $fail("ກະລຸນາເລືອກໃຫ້ຖືກຕ້ອງ...");
                                }
                            }

                ],
                'company_bank_account_id' => [
                    'required',
                        'array',
                            Rule::exists('company_bank_accounts', 'id')
                            ->whereNull('deleted_at'),
                            function ($attribute, $value, $fail) {
                                $user = auth()->user();

                                $userCompanyIds = CompanyUser::where('user_id', $user->id)
                                    ->pluck('company_id')
                                    ->toArray();

                                $validBankAccounts = CompanyBankAccount::whereIn('id', $value)
                                    ->whereIn('company_id', $userCompanyIds)
                                    ->get();

                                $validIds = $validBankAccounts->pluck('id')->toArray();

                                $invalidIds = array_diff($value, $validIds);

                                if (count($validBankAccounts) !== count($value)) {
                                    $fail("ກະລຸນາເລືອກຈໍານວນລາຍການທີ່ຖືກຕ້ອງສໍາລັບຜູ້ໃຊ້ {$user->id}. ID ບັນຊີທະນາຄານຂອງບໍລິສັດບໍ່ຖືກຕ້ອງ: " . implode(', ', $invalidIds));
                                }
                            }
                ],
            ];
        }

    }

    public function messages()
    {
        return [
            'invoice_name.required' => 'ກະລຸນາປ້ອນຊື່ກ່ອນ...',
            'invoice_name.max' => 'ຊື່ບໍ່ຄວນເກີນ 255 ໂຕອັກສອນ...',

            'start_date.required' => 'ກະລຸນາປ້ອນວັນທີກ່ອນ...',
            'start_date.date' => 'ຄວນເປັນວັນທີ...',
            'start_date.after_or_equal' => 'ວັນທີເລີ່ມຄວນເປັນວັນທີປັດຈຸບັນ...',
            'end_date.required' => 'ກະລຸນາປ້ອນວັນທີກ່ອນ...',
            'end_date.date' => 'ຄວນເປັນວັນທີ...',
            'end_date.after_or_equal' => 'ວັນທີສິ້ນສຸດຄວນໃຫ່ຍກວ່າວັນທີເລີ່ມ...',

            'discount.required' => 'ກະລຸນາປ້ອນກ່ອນ...',
            'discount.numeric' => 'ຄວນເປັນໂຕເລກ...',

            'quotation_detail_id.required' => 'ກະລຸນາປ້ອນກ່ອນ...',
            'quotation_detail_id.array' => 'quotation_detail_id ຄວນເປັນ array...',
            'quotation_detail_id.exists' => 'id ບໍ່ມີໃນລະບົບ...',

            'company_bank_account_id.required' => 'ກະລຸນາປ້ອນກ່ອນ...',
            'company_bank_account_id.array' => 'company_bank_account_id ຄວນເປັນ array...',
            'company_bank_account_id.exists' => 'id ບໍ່ມີໃນລະບົບ...',

            'id.required' => 'ກະລຸນາປ້ອນ id ກ່ອນ...',
            'id.numeric' => 'id ຄວນເປັນໂຕເລກ...',
            'id.exists' => 'id ບໍ່ມີໃນລະບົບ...',

            'order.required' => 'ກະລຸນາປ້ອນ order ກ່ອນ...',
            'order.numeric' => 'order ຄວນເປັນໂຕເລກ...',

            'name.required' => 'ກະລຸນາປ້ອນຊື່ກ່ອນ...',
            'name.max' => 'ຊື່ບໍ່ຄວນເກີນ 255 ໂຕອັກສອນ...',

            'amount.required' => 'ກະລຸນາປ້ອນຈຳນວນກ່ອນ...',
            'amount.numeric' => 'ຈຳນວນຄວນເປັນໂຕເລກ...',

            'price.required' => 'ກະລຸນາປ້ອນລາຄາກ່ອນ...',
            'price.numeric' => 'ລາຄາຄວນເປັນໂຕເລກ...',

            'description.max' => 'ລາຍລະອຽດບໍ່ຄວນເກີນ 255 ໂຕອັກສອນ...',

            'status.required' => 'ກະລຸນາປ້ອນສະຖານະກ່ອນ...',
            'status.in' => 'ສະຖານະຄວນມີຢູ່ໃນນີ້: created, approved, inprogress, completed, canceled...'
        ];
    }
}
