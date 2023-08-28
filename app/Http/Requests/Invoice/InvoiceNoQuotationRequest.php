<?php

namespace App\Http\Requests\Invoice;

use App\Models\Invoice;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class InvoiceNoQuotationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth('api')->user()->hasRole('superadmin|admin');
    }

    public function prepareForValidation()
    {
        if($this->isMethod('put') && $this->routeIs('edit.invoice.noQuotation')
             ||$this->isMethod('post') && $this->routeIs('add.invoice.detail.noQuotation')
             ||$this->isMethod('put') && $this->routeIs('edit.invoice.detail.noQuotation')
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
        if($this->isMethod('put') && $this->routeIs('edit.invoice.detail.noQuotation'))
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
                                    $checkItem = Invoice::where('id', $value)->where('status', 'approved')->exists();
                                    if($checkItem){
                                        $fail('ບໍ່ສາມາດແກ້ໄຂລາຍລະອຽດໃບເກັບເງິນນີ້ໄດ້ເພາະວ່າຖືກອະນຸມັດແລ້ວ...');
                                    }
                                }
                ],
                'order' => [
                    'required',
                        'numeric'
                ],
                'name' => [
                    'required',
                        'max:255'
                ],
                'amount' => [
                    'required',
                        'numeric'
                ],
                'price' => [
                    'required',
                        'numeric'
                ]
            ];
        }

        if($this->isMethod('post') && $this->routeIs('add.invoice.detail.noQuotation'))
        {
            return [
                'id' =>[
                    'required',
                        'numeric',
                            Rule::exists('invoices', 'id')
                                ->whereNull('deleted_at')
                ],
                'order' => [
                    'required',
                        'numeric'
                ],
                'name' => [
                    'required',
                        'max:255'
                ],
                'amount' => [
                    'required',
                        'numeric'
                ],
                'price' => [
                    'required',
                        'numeric'
                ]
            ];
        }

        if($this->isMethod('put') && $this->routeIs('edit.invoice.noQuotation'))
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
                                $checkItem = Invoice::where('id', $value)->where('status', 'approved')->exists();
                                if($checkItem){
                                    $fail('ບໍ່ສາມາດແກ້ໄຂໃບເກັບເງິນນີ້ໄດ້ເພາະວ່າຖືກອະນຸມັດແລ້ວ...');
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
                'customer_id' => [
                    'required',
                        'numeric',
                            Rule::exists('customers', 'id')
                                ->whereNull('deleted_at')
                ],
                'currency_id' => [
                    'required',
                        'numeric',
                            Rule::exists('currencies', 'id')
                                ->whereNull('deleted_at')
                ],
                'discount' => [
                    'required',
                        'numeric'
                ]
            ];
        }

        if($this->isMethod('post') && $this->routeIs('add.invoice.noQuotation'))
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
                'customer_id' => [
                    'required',
                        'numeric',
                            Rule::exists('customers', 'id')
                                ->whereNull('deleted_at')
                ],
                'quotation_id' => [
                    'nullable',
                        'numeric',
                            Rule::exists('quotations', 'id')
                                ->whereNull('deleted_at')
                ],
                'currency_id' => [
                    'nullable',
                        // 'numeric',
                        //     Rule::exists('currencies', 'id')
                        //         ->whereNull('deleted_at')
                ],
                'discount' => [
                    'required',
                        'numeric'
                ],
                'invoice_details' => [
                    'required',
                        'array'
                ],
                'invoice_details.*.order' => [
                    'required',
                        'numeric'
                ],
                'invoice_details.*.name' => [
                    'required',
                        'max:255'
                ],
                'invoice_details.*.amount' => [
                    'required',
                        'numeric'
                ],
                'invoice_details.*.price' => [
                    'required',
                        'numeric'
                ]
            ];
        }

    }
}
