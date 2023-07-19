<?php

namespace App\Http\Requests\Currency;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class CurrencyRequest extends FormRequest
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
        if($this->isMethod('put') && $this->routeIs('edit.currency')
             ||$this->isMethod('delete') && $this->routeIs('delete.currency')

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
        if($this->isMethod('delete') && $this->routeIs('delete.currency'))
        {
            return [
                'id' =>[
                    'required',
                    'numeric',
                    Rule::exists('currencies', 'id')
                ]
            ];
        }

        if($this->isMethod('put') && $this->routeIs('edit.currency'))
        {
            return [
                'id' =>[
                    'required',
                    'numeric',
                    Rule::exists('currencies', 'id')
                ],
                'name' =>[
                    'required',
                    'max:255',
                ],
                'short_name' =>[
                    'required',
                    'regex:/^(\$|₭|฿|¥)$/'
                ]
            ];
        }

        if($this->isMethod('post') && $this->routeIs('add.currency'))
        {
            return [
                'name' =>[
                    'required',
                    'max:255'
                ],
                'short_name' =>[
                    'required',
                    'regex:/^(\$|₭|฿|¥)$/'
                ]
            ];
        }

    }

    public function messages()
    {
        return [
            'name.required' => 'ກະລຸນາປ້ອນຊື່ສະກຸນກ່ອນ...',
            'name.max' => 'ຊື່ບໍ່ຄວນເກີນ 255 ໂຕອັກສອນ...',

            'short_name.required' => 'ກະລຸນາປ້ອນສັນຍາລັກກ່ອນ...',
            'short_name.regex' => 'ຄວນເປັນສັນຍາລັກ $|₭|฿|¥...',

            'id.required' => 'ກະລຸນາປ້ອນ ID ກ່ອນ...',
            'id.numeric' => 'ID ຄວນເປັນໂຕເລກ...',
            'id.exists' => 'ID ບໍ່ມີໃນລະບົບ...'
        ];
    }
}
