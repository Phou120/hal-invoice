<?php

namespace App\Http\Requests\Quotation;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class QuotationTypeRequest extends FormRequest
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
        if($this->isMethod('put') && $this->routeIs('update.quotation.type')
             || $this->isMethod('delete') && $this->routeIs('delete.quotation.type')

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
        if($this->isMethod('delete') && $this->routeIs('delete.quotation.type'))
        {
            return [
                'id' =>[
                    'required',
                        'numeric',
                            Rule::exists('quotation_types', 'id')
                ]
            ];
        }

        if($this->isMethod('put') && $this->routeIs('update.quotation.type'))
        {
            return [
                'id' =>[
                    'required',
                        'numeric',
                            Rule::exists('quotation_types', 'id')
                ],
                'name' =>[
                    'required',
                        'max:255'
                ],
                'rate' =>[
                    'required'
                ],
                'currency_id' => [
                    'required',
                        'numeric',
                            Rule::exists('currencies', 'id')
                                ->whereNull('deleted_at')
                ],
            ];
        }

        if($this->isMethod('post') && $this->routeIs('create.quotation.type'))
        {
            return [
                'name' =>[
                    'required',
                        'max:255'
                ],
                'rate' =>[
                    'required'
                ],
                'currency_id' => [
                    'required',
                        'numeric',
                            Rule::exists('currencies', 'id')
                                ->whereNull('deleted_at')
                ],
            ];
        }
    }

    public function messages()
    {
        return [
            'name.required' => 'ກະລຸນາປ້ອນຊື່ກ່ອນ...',
            'name.max' => 'ຊື່ບໍ່ຄວນເກີນ 255 ຕົວອັກສອນ...',

            'rate.required' => 'ກະລຸນາປ້ອນອັດຕາກ່ອນ',

            'id.required' => 'ກະລຸນາປ້ອນ id ກ່ອນ...',
            'id.numeric' => 'id ຄວນເປັນໂຕເລກ...',
            'id.exists' => 'id ບໍ່ມີໃນລະບົບ...',

            'currency_id.required' => 'ກະລຸນາປ້ອນ id ກ່ອນ...',
            'currency_id.numeric' => 'id ຄວນເປັນໂຕເລກ...',
            'currency_id.exists' => 'id ບໍ່ມີໃນລະບົບ...',
        ];
    }
}
