<?php

namespace App\Http\Requests\Quotation;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class QuotationRequest extends FormRequest
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
        if($this->isMethod('put') && $this->routeIs('edit.quotation')
            ||$this->isMethod('delete') && $this->routeIs('delete.quotation.detail')
            ||$this->isMethod('post') && $this->routeIs('add.quotation.detail')
            ||$this->isMethod('put') && $this->routeIs('edit.quotation.detail')
            ||$this->isMethod('delete') && $this->routeIs('delete.quotation')
            ||$this->isMethod('get') && $this->routeIs('list.quotation.detail')
            ||$this->isMethod('put') && $this->routeIs('update.quotation.status')
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
        if($this->isMethod('put') && $this->routeIs('update.quotation.status'))
        {
            return [
                'id' =>[
                    'required',
                        'numeric',
                            Rule::exists('quotations', 'id')->whereNull('deleted_at')
                ],
                'status' =>[
                    'required',
                        Rule::in('created', 'approved', 'inprogress', 'completed', 'canceled')
                ]
            ];
        }

        if($this->isMethod('get') && $this->routeIs('list.quotation.detail'))
        {
            return [
                'id' =>[
                    'required',
                        'numeric',
                            Rule::exists('quotations', 'id')
                                ->whereNull('deleted_at')
                ],
            ];
        }

        if($this->isMethod('delete') && $this->routeIs('delete.quotation'))
        {
            return [
                'id' =>[
                    'required',
                        'numeric',
                            Rule::exists('quotations', 'id')
                            ->whereNull('deleted_at')
                ]
            ];
        }

        if($this->isMethod('delete') && $this->routeIs('delete.quotation.detail'))
        {
            return [
                'id' =>[
                    'required',
                        'numeric',
                            Rule::exists('quotation_details', 'id')

                ]
            ];
        }

        if($this->isMethod('put') && $this->routeIs('edit.quotation.detail'))
        {
            return [
                'id' =>[
                    'required',
                        'numeric',
                            Rule::exists('quotation_details', 'id')
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

        if($this->isMethod('put') && $this->routeIs('edit.quotation'))
        {
            return [
                'id' =>[
                    'required',
                        'numeric',
                            Rule::exists('quotations', 'id')
                                ->whereNull('deleted_at')
                ],
                'quotation_name' =>[
                    'required',
                        'max:255'
                ],
                'start_date' => [
                    'required',
                        'date'
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

        if($this->isMethod('post') && $this->routeIs('add.quotation.detail'))
        {
            return [
                'id' =>[
                    'required',
                        'numeric',
                            Rule::exists('quotations', 'id')
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

        if($this->isMethod('post') && $this->routeIs('add.quotation'))
        {
            return [
                'quotation_name' =>[
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
                'note' => [
                    'nullable',
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
                ],
                'quotation_details' => [
                    'required',
                        'array'
                ],
                'quotation_details.*.order' => [
                    'required',
                        'numeric'
                ],
                'quotation_details.*.name' => [
                    'required',
                        'max:255'
                ],
                'quotation_details.*.amount' => [
                    'required',
                        'numeric'
                ],
                'quotation_details.*.price' => [
                    'required',
                        'numeric'
                ],
                'quotation_details.*.description' => [
                    'nullable'
                ]
            ];
        }

    }

    public function messages()
    {
        return [
            'quotation_name.required' => 'ກະລຸນາປ້ອນຊື່ກ່ອນ...',
            'quotation_name.max' => 'ຊື່ບໍ່ຄວນເກີນ 255 ໂຕອັກສອນ...',

            'start_date.required' => 'ກະລຸນາປ້ອນວັນທີກ່ອນ...',
            'start_date.date' => 'ຄວນເປັນວັນທີ...',
            'start_date.after_or_equal' => 'ວັນທີເລີ່ມຄວນເປັນວັນທີປັດຈຸບັນ...',
            'end_date.required' => 'ກະລຸນາປ້ອນວັນທີກ່ອນ...',
            'end_date.date' => 'ຄວນເປັນວັນທີ...',
            'end_date.after_or_equal' => 'ວັນທີສິ້ນສຸດຄວນໃຫ່ຍກວ່າວັນທີເລີ່ມ...',

            'note.max' => 'ຄຳອະທິບາຍບໍ່ຄວນເກີນ 255 ໂຕອັກສອນ...',

            'customer_id.required' => 'ກະລຸນາປ້ອນ id ກ່ອນ...',
            'customer_id.numeric' => 'id ຄວນເປັນໂຕເລກ...',
            'customer_id.exists' => 'id ບໍ່ມີໃນລະບົບ...',

            'currency_id.required' => 'ກະລຸນາປ້ອນ id ກ່ອນ...',
            'currency_id.numeric' => 'id ຄວນເປັນໂຕເລກ...',
            'currency_id.exists' => 'id ບໍ່ມີໃນລະບົບ...',

            'discount.required' => 'ກະລຸນາປ້ອນກ່ອນ...',
            'discount.numeric' => 'ຄວນເປັນໂຕເລກ...',

            'quotation_details.required' => 'ກະລຸນາປ້ອນກ່ອນ...',
            'quotation_details.array' => 'quotation_details ຄວນເປັນ array...',

            'quotation_details.*.order.required' => 'ກະລຸນາປ້ອນ order ກ່ອນ...',
            'quotation_details.*.order.numeric' => 'order ຄວນເປັນໂຕເລກ...',

            'quotation_details.*.name.required' => 'ກະລຸນາປ້ອນຊື່ກ່ອນ...',
            'quotation_details.*.name.max' => 'ຊື່ບໍ່ຄວນເກີນ 255 ໂຕອັກສອນ...',

            'quotation_details.*.amount.required' => 'ກະລຸນາປ້ອນຈຳນວນກ່ອນ...',
            'quotation_details.*.amount.numeric' => 'ຈຳນວນຄວນເປັນໂຕເລກ...',

            'quotation_details.*.price.required' => 'ກະລຸນາປ້ອນລາຄາກ່ອນ...',
            'quotation_details.*.price.numeric' => 'ລາຄາຄວນເປັນໂຕເລກ...',

            'quotation_details.*.description.max' => 'ລາຍລະອຽດບໍ່ຄວນເກີນ 255 ໂຕອັກສອນ...',

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
