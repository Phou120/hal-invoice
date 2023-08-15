<?php

namespace App\Http\Requests\Customers;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class CustomerRequest extends FormRequest
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
        if($this->isMethod('put') && $this->routeIs('edit.customer')
             ||$this->isMethod('delete') && $this->routeIs('delete.customer')

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
        /** ລຶບຂໍ້ມູນລູກຄ້າ */
        if($this->isMethod('delete') && $this->routeIs('delete.customer'))
        {
            return [
                'id' =>[
                    'required',
                    'numeric',
                    Rule::exists('customers', 'id')
                ]
            ];
        }

        /** ແກ້ໄຂຂໍ້ມູນລູກຄ້າ */
        if($this->isMethod('put') && $this->routeIs('edit.customer'))
        {
            return [
                'id' =>[
                    'required',
                    'numeric',
                    Rule::exists('customers', 'id')
                ],
                'company_name' =>[
                    'required',
                    'max:255'
                ],
                'phone' => [
                    'required',
                    'numeric',
                    'digits_between:6,15',
                    Rule::unique('customers', 'phone')
                    ->ignore($this->id)
                    ->whereNull('deleted_at')
                ],
                'email' => [
                    'nullable',
                    'email',
                    'max:255',
                    'min:5',
                    Rule::unique('customers', 'email')
                    ->ignore($this->id)
                    ->whereNull('deleted_at')
                ],
                'logo' =>[
                    'required',
                    'mimes:jpg,png,jpeg,gif',
                    'max:2048',
                ]
            ];
        }

        /** ບັນທຶກຂໍ້ມູນລູກຄ້າ */
        if($this->isMethod('post') && $this->routeIs('add.customer'))
        {
            return [
                'company_name' =>[
                    'required',
                    'max:255'
                ],
                'phone' => [
                    'required',
                    'numeric',
                    'digits_between:6,15',
                    Rule::unique('customers', 'phone')
                    ->whereNull('deleted_at')
                ],
                'email' => [
                    'nullable',
                    'email',
                    'max:255',
                    'min:5',
                    Rule::unique('customers', 'email')
                    ->whereNull('deleted_at')
                ],
                'logo' =>[
                    'required',
                    'mimes:jpg,png,jpeg,gif',
                    'max:2048',
                ]
            ];
        }

    }

    /** ສະແດງຂໍ້ຄວາມ */
    public function messages()
    {
        return [
            'company_name.required' => 'ກະລຸນາປ້ອນຊື່ກ່ອນ...',
            'company_name.max' => 'ຊື່ບໍ່ຄວນເກີນ 255 ໂຕອັກສອນ...',

            'phone.required' => 'ກະລຸນາປ້ອນເບີໂທລະສັບກ່ອນ...',
            'phone.numeric' => 'ເບີໂທລະສັບຄວນເປັນໂຕເລກ...',
            'phone.digits_between' => 'ບໍ່ຄວນສັ້ນກວ່າ 6 ເເລະ ເກີນ 15 ໂຕເລກ...',
            'phone.unique' => 'ເບີໂທລະສັບນີ້ມີໃນລະບົບເເລ້ວ...',

            'email.required' => 'ກະລຸນາປ້ອນອີເມວກ່ອນ...',
            'email.email' => 'ອີເມວບໍ່ຖືກຕ້ອງ...',
            'email.max' => 'ອີເມວບໍ່ຄວນເກີນ 255 ໂຕອັກສອນ...',
            'email.min' => 'ອີເມວບໍ່ຄວນສັ້ນກວ່າ 5 ໂຕອັກສອນ...',
            'email.unique' => 'ອີເມວນີ້ມີໃນລະບົບເເລ້ວ',

            'logo.required' => 'ກະລຸນາປ້ອນ logo ກ່ອນ...',
            'logo.mimes' => 'ຄວນເປັນນາມສະກຸນໄຟລ jpeg,jpg,png,gif',
            'logo.max' => 'ຂະໜາດບໍ່ຄວນເກີນ 2048',

            'address.required' => 'ກະລຸນາປ້ອນທີ່ຢູ່ກ່ອນ...',

            'id.required' => 'ກະລຸນາປ້ອນ ID ກ່ອນ...',
            'id.numeric' => 'ID ຄວນເປັນໂຕເລກ...',
            'id.exists' => 'ID ບໍ່ມີໃນລະບົບ...',
        ];
    }
}
