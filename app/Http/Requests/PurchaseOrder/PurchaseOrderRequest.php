<?php

namespace App\Http\Requests\PurchaseOrder;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class PurchaseOrderRequest extends FormRequest
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
        if($this->isMethod('put') && $this->routeIs('edit.purchase.order')
            ||$this->isMethod('delete') && $this->routeIs('delete.purchase.order')
            ||$this->isMethod('post') && $this->routeIs('add.purchase.detail')
            ||$this->isMethod('put') && $this->routeIs('edit.purchase.detail')
            ||$this->isMethod('delete') && $this->routeIs('delete.purchase.detail')
            ||$this->isMethod('get') && $this->routeIs('list.purchase.detail')

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
        if($this->isMethod('get') && $this->routeIs('list.purchase.detail'))
        {
            return [
                'id' =>[
                    'required',
                        'numeric',
                            Rule::exists('purchase_orders', 'id')
                                ->whereNull('deleted_at')
                ],
            ];
        }

        if($this->isMethod('delete') && $this->routeIs('delete.purchase.order'))
        {
            return [
                'id' =>[
                    'required',
                        'numeric',
                            Rule::exists('purchase_orders', 'id')
                                ->whereNull('deleted_at')
                ]
            ];
        }

        if($this->isMethod('delete') && $this->routeIs('delete.purchase.detail'))
        {
            return [
                'id' =>[
                    'required',
                        'numeric',
                            Rule::exists('purchase_details', 'id')
                                ->whereNull('deleted_at')
                ]
            ];
        }

        if($this->isMethod('put') && $this->routeIs('edit.purchase.detail'))
        {
            return [
                'id' =>[
                    'required',
                        'numeric',
                            Rule::exists('purchase_details', 'id')
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

        if($this->isMethod('put') && $this->routeIs('edit.purchase.order'))
        {
            return [
                'id' =>[
                    'required',
                        'numeric',
                            Rule::exists('purchase_orders', 'id')
                                ->whereNull('deleted_at')
                ],
                'invoice_id' =>[
                    'required',
                        'numeric',
                            Rule::exists('invoices', 'id')
                                ->whereNull('deleted_at')
                ],
                'purchase_name' =>[
                    'required',
                        'max:255'
                ],
                'date' =>[
                    'required',
                        'date'
                ],
                'note' =>[
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

        if($this->isMethod('post') && $this->routeIs('add.purchase.detail'))
        {
            return [
                'id' =>[
                    'required',
                        'numeric',
                            Rule::exists('purchase_orders', 'id')
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

        if($this->isMethod('post') && $this->routeIs('add.purchase.order'))
        {
            return [
                'invoice_id' =>[
                    'required',
                        'numeric',
                            Rule::exists('invoices', 'id')
                                ->whereNull('deleted_at')
                ],
                'purchase_name' =>[
                    'required',
                        'max:255'
                ],
                'date' =>[
                    'required',
                        'date'
                ],
                'note' =>[
                    'nullable',
                        'max:255'
                ]
            ];
        }

    }

    public function messages()
    {
        return [
            'invoice_id.required' => 'ກະລຸນາປ້ອນ id ກ່ອນ...',
            'invoice_id.numeric' => 'id ຄວນເປັນໂຕເລກ...',
            'invoice_id.exists' => 'id ບໍ່ມີໃນລະບົບ...',

            'purchase_name.required' => 'ກະລຸນາປ້ອນຊື່ກ່ອນ...',
            'purchase_name.max' => 'ຊື່ບໍ່ຄວນເກີນ 255 ໂຕອັກສອນ...',

            'date.required' => 'ກະລຸນາປ້ອນວັນທີກ່ອນ...',
            'date.date' => 'ຄວນເປັນວັນທີເດືອນປີ...',

            'note.max' => 'ຄຳອະທິບາຍບໍ່ຄວນເກີນ 255 ໂຕອັກສອນ...',

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

        ];
    }
}
