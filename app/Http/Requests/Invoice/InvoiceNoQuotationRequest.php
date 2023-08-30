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
        if($this->isMethod('put') && $this->routeIs('edit.invoice.no.quotation')
             ||$this->isMethod('post') && $this->routeIs('add.invoice.no.quotation.detail')
             ||$this->isMethod('put') && $this->routeIs('edit.invoice.no.quotation.detail')
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
        if($this->isMethod('put') && $this->routeIs('edit.invoice.no.quotation.detail'))
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
                                },
                                function ($attribute, $value, $fail) {
                                    $checkItem = Invoice::where('id', $value)->whereNotNull('quotation_id')->exists();
                                    if ($checkItem) {
                                        $fail('id ນີ້ແມ່ນຂອງ invoice ທີ່ມີ quotation...');
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

        if($this->isMethod('post') && $this->routeIs('add.invoice.no.quotation.detail'))
        {
            return [
                'id' =>[
                    'required',
                        'numeric',
                            Rule::exists('invoices', 'id')
                            ->where(function ($query) {
                                $query->whereNull('deleted_at');
                            }),
                            function ($attribute, $value, $fail) {
                                $checkItem = Invoice::where('id', $value)->whereNotNull('quotation_id')->exists();
                                if ($checkItem) {
                                    $fail('id ນີ້ແມ່ນຂອງ invoice ທີ່ມີ quotation...');
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

        if($this->isMethod('put') && $this->routeIs('edit.invoice.no.quotation'))
        {
            return [
                'id' =>[
                    'required',
                        'numeric',
                            Rule::exists('invoices', 'id')
                                ->where(function ($query) {
                                    $query->whereNull('deleted_at');
                                }),
                            function($attribute, $value, $fail){
                                $checkItem = Invoice::where('id', $value)->where('status', 'approved')->exists();
                                if($checkItem){
                                    $fail('ບໍ່ສາມາດແກ້ໄຂໃບເກັບເງິນນີ້ໄດ້ເພາະວ່າຖືກອະນຸມັດແລ້ວ...');
                                }
                            },
                            function ($attribute, $value, $fail) {
                                $checkItem = Invoice::where('id', $value)->whereNotNull('quotation_id')->exists();
                                if ($checkItem) {
                                    $fail('id ນີ້ແມ່ນຂອງ invoice ທີ່ມີ quotation...');
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

        if($this->isMethod('post') && $this->routeIs('add.invoice.no.quotation'))
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
                    'required',
                        'numeric',
                            Rule::exists('currencies', 'id')
                                ->whereNull('deleted_at')
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

            'customer_id.required' => 'ກະລຸນາປ້ອນ id ກ່ອນ...',
            'customer_id.numeric' => 'id ຄວນເປັນໂຕເລກ...',
            'customer_id.exists' => 'id ບໍ່ມີໃນລະບົບ...',

            'quotation_id.numeric' => 'id ຄວນເປັນໂຕເລກ...',
            'quotation_id.exists' => 'id ບໍ່ມີໃນລະບົບ...',

            'currency_id.required' => 'ກະລຸນາປ້ອນ id ກ່ອນ...',
            'currency_id.numeric' => 'id ຄວນເປັນໂຕເລກ...',
            'currency_id.exists' => 'id ບໍ່ມີໃນລະບົບ...',

            'discount.required' => 'ກະລຸນາປ້ອນກ່ອນ...',
            'discount.numeric' => 'ຄວນເປັນໂຕເລກ...',

            // 'quotation_detail_id.required' => 'ກະລຸນາປ້ອນກ່ອນ...',
            // 'quotation_detail_id.array' => 'quotation_detail_id ຄວນເປັນ array...',
            // 'quotation_detail_id.exists' => 'id ບໍ່ມີໃນລະບົບ...',

            'invoice_details.*.order.required' => 'ກະລຸນາປ້ອນ order ກ່ອນ...',
            'invoice_details.*.order.numeric' => 'order ຄວນເປັນໂຕເລກ...',

            'invoice_details.*.name.required' => 'ກະລຸນາປ້ອນຊື່ກ່ອນ...',
            'invoice_details.*.name.max' => 'ຊື່ບໍ່ຄວນເກີນ 255 ໂຕອັກສອນ...',

            'invoice_details.*.amount.required' => 'ກະລຸນາປ້ອນຈຳນວນກ່ອນ...',
            'invoice_details.*.amount.numeric' => 'ຈຳນວນຄວນເປັນໂຕເລກ...',

            'invoice_details.*.price.required' => 'ກະລຸນາປ້ອນລາຄາກ່ອນ...',
            'invoice_details.*.price.numeric' => 'ລາຄາຄວນເປັນໂຕເລກ...',

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
