<?php

namespace App\Http\Requests\Company;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class CompanyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth('api')->user()->hasRole('superAdmin|admin');
        // if($this->routeIs('edit.company')){
        //     return auth('api')->user()->hasRole('superAdmin');
        // }else if($this->routeIs('add.company')) {
        //     return auth('api')->user()->hasRole('superAdmin|admin');
        // }else if($this->routeIs('deleted.company')){
        //     return auth('api')->user()->hasRole('superAdmin');
        // }
    }

    public function prepareForValidation()
    {
        if($this->isMethod('post') && $this->routeIs('edit.company')
            || $this->isMethod('delete') && $this->routeIs('delete.company')

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
        if($this->isMethod('delete') && $this->routeIs('delete.company'))
        {
            return [
                'id' =>[
                    'required',
                    'numeric',
                    Rule::exists('companies', 'id')
                        ->whereNull('deleted_at')
                ]
            ];
        }

        if($this->isMethod('post') && $this->routeIs('edit.company'))
        {
            return [
                'id' =>[
                    'required',
                    'numeric',
                    Rule::exists('companies', 'id')
                    ->whereNull('deleted_at')
                ],
                'company_name' =>[
                    'required',
                    'max:255',
                ],
                'phone' =>[
                    'required',
                    'numeric',
                    'digits_between:6,15',
                    Rule::unique('companies', 'phone')
                    ->ignore($this->id)
                    ->whereNull('deleted_at')
                ],
                'email' => [
                    'nullable',
                    'email',
                    'max:255',
                    'min:5',
                    Rule::unique('companies', 'email')
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

        if($this->isMethod('post') && $this->routeIs('add.company'))
        {
            return [
                'company_name' =>[
                    'required',
                    'max:255'
                ],
                'phone' =>[
                    'required',
                    'numeric',
                    'digits_between:6,15',
                    Rule::unique('companies', 'phone')
                    ->whereNull('deleted_at')
                ],
                'email' => [
                    'nullable',
                    'email',
                    'max:255',
                    'min:5',
                    Rule::unique('companies', 'email')
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

    public function messages()
    {
        return [
            'company_name.required' => 'ກະລຸນາປ້ອນຊື່ກ່ອນ...',
            'company_name.max' => 'ຊື່ບໍ່ຄວນເກີນ 255 ໂຕອັກສອນ...',

            'phone.required' => 'ກະລຸນາປ້ອນເບີໂທລະສັບກ່ອນ...',
            'phone.numeric' => 'ເບີໂທລະສັບຄວນເປັນໂຕເລກ...',
            'phone.digits_between' => 'ບໍ່ຄວນສັ້ນກວ່າ 6 ເເລະ ເກີນ 15 ໂຕເລກ...',
            'phone.unique' => 'ເບີໂທລະສັບນີ້ມີໃນລະບົບເເລ້ວ...',

            'email.email' => 'ອີເມວບໍ່ຖືກຕ້ອງ...',
            'email.max' => 'ອີເມວບໍ່ຄວນເກີນ 255 ໂຕອັກສອນ...',
            'email.min' => 'ອີເມວບໍ່ຄວນສັ້ນກວ່າ 5 ໂຕອັກສອນ...',
            'email.unique' => 'ອີເມວນີ້ມີໃນລະບົບເເລ້ວ',

            'logo.required' => 'ກະລຸນາປ້ອນ logo ກ່ອນ....',
            'logo.mimes' => 'ຄວນເປັນນາມສະກຸນໄຟລ jpeg,jpg,png,gif',
            'logo.max' => 'ຂະໜາດບໍ່ຄວນເກີນ 2048',

            'id.required' => 'ກະລຸນາປ້ອນ ID ກ່ອນ...',
            'id.numeric' => 'ID ຄວນເປັນໂຕເລກ...',
            'id.exists' => 'ID ບໍ່ມີໃນລະບົບ...',
        ];
    }
}
