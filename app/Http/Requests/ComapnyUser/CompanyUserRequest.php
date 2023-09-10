<?php

namespace App\Http\Requests\ComapnyUser;

use App\Models\User;
use App\Models\CompanyUser;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Http\FormRequest;

class CompanyUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth('api')->user()->hasRole('company-admin|company-user');
    }

    public function prepareForValidation()
    {
        if($this->isMethod('put') && $this->routeIs('update.company.user')
            || $this->isMethod('delete') && $this->routeIs('delete.company.user')

        ){
            $this->merge([
                'id' => $this->route()->parameters['id'],
                'user_id' => $this->getUserId($this->route()->parameters['id'])

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
        if($this->isMethod('delete') && $this->routeIs('delete.company.user'))
        {
            return [
                'id' =>[
                    'required',
                        'numeric',
                            Rule::exists('company_users', 'id')->whereNull('deleted_at')
                ]
            ];
        }

        if($this->isMethod('put') && $this->routeIs('update.company.user'))
        {
            return [
                'id' =>[
                    'required',
                        'numeric',
                            Rule::exists('company_users', 'id')
                                ->whereNull('deleted_at')
                ],
                'name' =>[
                    'required',
                        'max:255'
                ],
                'email' => [
                    'required',
                        'max:255',
                            'email',
                                'min:5',
                                Rule::unique('users', 'email')->ignore($this->id)->whereNull('deleted_at')
                ]
            ];
        }

        if($this->isMethod('post') && $this->routeIs('create.company.user'))
        {
            return [
                'company_id' =>[
                    'required',
                        'numeric',
                            Rule::exists('companies', 'id')
                                ->whereNull('deleted_at')
                ],
                'name' =>[
                    'required',
                        'max:255'
                ],
                'email' => [
                    'required',
                        'max:255',
                            'email',
                                'min:5',
                                    Rule::unique('users', 'email')
                                        ->whereNull('deleted_at')
                ],
                'password' =>[
                    'required',
                        'min:6',
                            'max:15'
                ]
            ];
        }
    }

    public function messages()
    {
        return [
            'company_id.required' => 'ກະລຸນາປ້ອນ id ກ່ອນ...',
            'company_id.numeric' => 'id ຄວນເປັນໂຕເລກ...',
            'company_id.exists' => 'id ບໍ່ມີໃນລະບົບ...',

            'name.required' => 'ກະລຸນາປ້ອນຊື່ກ່ອນ...',
            'name.max' => 'ຊື່ບໍ່ຄວນເກີນ 255 ໂຕອັກສອນ...',

            'email.required' => 'ກະລຸນາປ້ອນອີເມວກ່ອນ...',
            'email.email' => 'ອີເມວບໍ່ຖືກຕ້ອງ...',
            'email.max' => 'ອີເມວບໍ່ຄວນເກີນ 255 ໂຕອັກສອນ...',
            'email.min' => 'ອີເມວບໍ່ຄວນສັ້ນກວ່າ 5 ໂຕອັກສອນ...',
            'email.unique' => 'ອີເມວນີ້ມີໃນລະບົບເເລ້ວ',

            'password.required' => 'ກະລຸນາປ້ອນລະຫັດກ່ອນ',
            'password.max' => 'ອີເມວບໍ່ຄວນເກີນ 15 ໂຕອັກສອນ...',
            'password.min' => 'ອີເມວບໍ່ຄວນສັ້ນກວ່າ 6 ໂຕອັກສອນ...',

            'id.required' => 'ກະລຸນາປ້ອນ id ກ່ອນ...',
            'id.numeric' => 'id ຄວນເປັນໂຕເລກ...',
            'id.exists' => 'id ບໍ່ມີໃນລະບົບ...'
        ];
    }

    public function getUserId($companyUserId){
        $companyUser = CompanyUser::find($companyUserId);
        return $companyUser ? $companyUser['user_id'] : null;
    }
}
