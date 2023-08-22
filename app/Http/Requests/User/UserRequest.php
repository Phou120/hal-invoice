<?php

namespace App\Http\Requests\User;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
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
        if($this->isMethod('put') && $this->routeIs('edit.user')
             ||$this->isMethod('delete') && $this->routeIs('delete.user')
             ||$this->isMethod('put') && $this->routeIs('change.password')

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
        if($this->isMethod('put') && $this->routeIs('change.password'))
        {
            return [
                'id' =>[
                    'required',
                        'numeric',
                            Rule::exists('users', 'id')
                            ->whereNull('deleted_at')
                ],
                'password' =>[
                    // 'required',
                        'min:6',
                            'max:15'
                ]
            ];
        }

        if($this->isMethod('delete') && $this->routeIs('delete.user'))
        {
            return [
                'id' =>[
                    'required',
                        'numeric',
                            Rule::exists('users', 'id')
                            ->whereNull('deleted_at')
                ]
            ];
        }

        if($this->isMethod('put') && $this->routeIs('edit.user'))
        {
            return [
                'id' =>[
                    'required',
                        'numeric',
                            Rule::exists('users', 'id')
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
                                        ->ignore($this->id)
                                        ->whereNull('deleted_at')

                ]
            ];
        }

        if($this->isMethod('post') && $this->routeIs('add.user'))
        {
            return [
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
}
