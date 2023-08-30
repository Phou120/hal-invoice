<?php

namespace App\Http\Requests\ComapnyUser;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class CompanyBankAccountRequest extends FormRequest
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
        if($this->isMethod('put') && $this->routeIs('update.bank.account')
             || $this->isMethod('delete') && $this->routeIs('delete.bank.account')
             || $this->isMethod('put') && $this->routeIs('update.status.bank.account')
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
        if($this->isMethod('put') && $this->routeIs('update.status.bank.account'))
        {
            return[
                'id' =>[
                    'required',
                        'numeric',
                            Rule::exists('company_bank_accounts', 'id')->whereNull('deleted_at')
                ],
                'status' =>[
                    'required',
                        Rule::in('active', 'inactive')
                ]
            ];
        }

        if($this->isMethod('delete') && $this->routeIs('delete.bank.account'))
        {
            return [
                'id' =>[
                    'required',
                        'numeric',
                            Rule::exists('company_bank_accounts', 'id')->whereNull('deleted_at')
                ]
            ];
        }

        if($this->isMethod('put') && $this->routeIs('update.bank.account'))
        {
            return [
                'id' =>[
                    'required',
                        'numeric',
                            Rule::exists('company_bank_accounts', 'id')->whereNull('deleted_at')
                ],
                'company_id' =>[
                    'required',
                        'numeric',
                            Rule::exists('companies', 'id')->whereNull('deleted_at')
                ],
                'bank_name' =>[
                    'required',
                        'max:255'
                ],
                'account_name' =>[
                    'required',
                        'max:255'
                ],
                'account_number' =>[
                    'required',
                ]
            ];
        }

        if($this->isMethod('post') && $this->routeIs('create.bank.account'))
        {
            return [
                'company_id' =>[
                    'required',
                        'numeric',
                            Rule::exists('companies', 'id')->whereNull('deleted_at')
                ],
                'bank_name' =>[
                    'required',
                        'max:255'
                ],
                'account_name' =>[
                    'required',
                        'max:255'
                ],
                'account_number' =>[
                    'required'
                ]
            ];
        }
    }

    public function messages()
    {
        return [
            'company_id.required' => 'ກະລຸນາປ້ອນ id ບໍລິສັດກ່ອນ...',
            'company_id.numeric' => 'id ບໍລິສັດຄວນເປັນໂຕເລກ...',
            'company_id.exists' => 'id ບໍ່ມີໃນລະບົບ...',

            'bank_name.required' => 'ກະລຸນາປ້ອນຊື່ທະນາຄານກ່ອນ...',
            'bank_name.max' => 'ຊື່ທະນາຄານບໍ່ຄວນເກີນ 255 ຕົວອັກສອນ...',

            'account_name.required' => 'ກະລຸນາປ້ອນຊື່ບັນຊີກ່ອນ...',
            'account_name.max' => 'ຊື່ບັນຊີບໍ່ຄວນເກີນ 255 ຕົວອັກສອນ...',

            'account_number.required' => 'ກະລຸນາປ້ອນເລກບັນຊີກ່ອນ...',

            'id.required' => 'ກະລຸນາປ້ອນ id ກ່ອນ...',
            'id.numeric' => 'id ຄວນເປັນໂຕເລກ...',
            'id.exists' => 'id ບໍ່ມີໃນລະບົບ...',

            'status.required' => 'ກະລຸນາປ້ອນສະຖານະກ່ອນ...',
            'status.in' => 'ສະຖານະຄວນມີຢູ່ໃນນີ້: active and inactive...'
        ];
    }
}
