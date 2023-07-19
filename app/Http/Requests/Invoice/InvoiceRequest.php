<?php

namespace App\Http\Requests\Invoice;

use Illuminate\Validation\Rule;
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
        return auth('api')->user()->hasRole('superAdmin|admin');
    }


    public function prepareForValidation()
    {
        if($this->isMethod('put') && $this->routeIs('edit.invoice')
            ||$this->isMethod('delete') && $this->routeIs('delete.invoice')
            ||$this->isMethod('put') && $this->routeIs('edit.invoice.detail')
            ||$this->isMethod('post') && $this->routeIs('add.invoice.detail')
            ||$this->isMethod('put') && $this->routeIs('update.invoice.status')
            ||$this->isMethod('delete') && $this->routeIs('delete.invoice.detail')

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
                        Rule::in('pending', 'paid', 'created', 'cancelled')
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
                                ->whereNull('deleted_at')
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
                                ->whereNull('deleted_at')
                ],
            ];
        }

        if($this->isMethod('put') && $this->routeIs('edit.invoice.detail'))
        {
            return [
                'id' =>[
                    'required',
                        'numeric',
                            Rule::exists('invoice_details', 'id')
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
                ],
                'description' => [
                    'nullable',
                        'max:255'
                ]
            ];
        }

        if($this->isMethod('put') && $this->routeIs('edit.invoice'))
        {
            return [
                'id' =>[
                    'required',
                        'numeric',
                            Rule::exists('invoices', 'id')
                                ->whereNull('deleted_at')
                ],
                'invoice_name' =>[
                    'required',
                        'max:255'
                ],
                'start_date' => [
                    'required',
                        'date'
                ],
                'end_date' => [
                    'required',
                        'date'
                ],
                'note' => [
                    'nullable',
                        'max:255'
                ],
                'customer_id' => [
                    'required',
                        'numeric',
                            Rule::exists('customers', 'id')
                                ->whereNull('deleted_at')
                ],
                'company_id' => [
                    'required',
                        'numeric',
                            Rule::exists('companies', 'id')
                                ->whereNull('deleted_at')
                ],
                'currency_id' => [
                    'required',
                        'numeric',
                            Rule::exists('currencies', 'id')
                                ->whereNull('deleted_at')
                ],
                'tax' => [
                    'required',
                        'numeric'
                ],
                'discount' => [
                    'required',
                        'numeric'
                ]
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
                ],
                'description' => [
                    'nullable',
                        'max:255'
                ]
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
                        'date'
                ],
                'end_date' => [
                    'required',
                        'date'
                ],
                'note' => [
                    'nullable',
                        'max:255'
                ],
                'customer_id' => [
                    'required',
                        'numeric',
                            Rule::exists('customers', 'id')
                                ->whereNull('deleted_at')
                ],
                'company_id' => [
                    'required',
                        'numeric',
                            Rule::exists('companies', 'id')
                                ->whereNull('deleted_at')
                ],
                'currency_id' => [
                    'required',
                        'numeric',
                            Rule::exists('currencies', 'id')
                                ->whereNull('deleted_at')
                ],
                'tax' => [
                    'required',
                        'numeric'
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
                ],
                'invoice_details.*.description' => [
                    'nullable',
                        'max:255'
                ]
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
            'end_date.required' => 'ກະລຸນາປ້ອນວັນທີກ່ອນ...',
            'end_date.date' => 'ຄວນເປັນວັນທີ...',

            'note.max' => 'ຄຳອະທິບາຍບໍ່ຄວນເກີນ 255 ໂຕອັກສອນ...',

            'customer_id.required' => 'ກະລຸນາປ້ອນ id ກ່ອນ...',
            'customer_id.numeric' => 'id ຄວນເປັນໂຕເລກ...',
            'customer_id.exists' => 'id ບໍ່ມີໃນລະບົບ...',

            'company_id.required' => 'ກະລຸນາປ້ອນ id ກ່ອນ...',
            'company_id.numeric' => 'id ຄວນເປັນໂຕເລກ...',
            'company_id.exists' => 'id ບໍ່ມີໃນລະບົບ...',

            'currency_id.required' => 'ກະລຸນາປ້ອນ id ກ່ອນ...',
            'currency_id.numeric' => 'id ຄວນເປັນໂຕເລກ...',
            'currency_id.exists' => 'id ບໍ່ມີໃນລະບົບ...',

            'tax.required' => 'ກະລຸນາປ້ອນກ່ອນ...',
            'tax.numeric' => 'ຄວນເປັນໂຕເລກ...',

            'discount.required' => 'ກະລຸນາປ້ອນກ່ອນ...',
            'discount.numeric' => 'ຄວນເປັນໂຕເລກ...',

            'invoice_details.required' => 'ກະລຸນາປ້ອນກ່ອນ...',
            'invoice_details.array' => 'invoice_details ຄວນເປັນ array...',

            'invoice_details.*.order.required' => 'ກະລຸນາປ້ອນ order ກ່ອນ...',
            'invoice_details.*.order.numeric' => 'order ຄວນເປັນໂຕເລກ...',

            'invoice_details.*.name.required' => 'ກະລຸນາປ້ອນຊື່ກ່ອນ...',
            'invoice_details.*.name.max' => 'ຊື່ບໍ່ຄວນເກີນ 255 ໂຕອັກສອນ...',

            'invoice_details.*.amount.required' => 'ກະລຸນາປ້ອນຈຳນວນກ່ອນ...',
            'invoice_details.*.amount.numeric' => 'ຈຳນວນຄວນເປັນໂຕເລກ...',

            'invoice_details.*.price.required' => 'ກະລຸນາປ້ອນລາຄາກ່ອນ...',
            'invoice_details.*.price.numeric' => 'ລາຄາຄວນເປັນໂຕເລກ...',

            'invoice_details.*.description.max' => 'ລາຍລະອຽດບໍ່ຄວນເກີນ 255 ໂຕອັກສອນ...',

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
            'status.in' => 'ສະຖານະຄວນມີຢູ່ໃນນີ້: pending, paid, created, cancelled...'
        ];
    }
}
