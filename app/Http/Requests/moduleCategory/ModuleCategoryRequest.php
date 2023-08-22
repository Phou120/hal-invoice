<?php

namespace App\Http\Requests\moduleCategory;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class ModuleCategoryRequest extends FormRequest
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
        if($this->isMethod('put') && $this->routeIs('update.module.category')
             || $this->isMethod('delete') && $this->routeIs('delete.module.category')

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
        if($this->isMethod('delete') && $this->routeIs('delete.module.category'))
        {
            return [
                'id' =>[
                    'required',
                        'numeric',
                            Rule::exists('module_categories', 'id')->whereNull('deleted_at')
                ]
            ];
        }

        if($this->isMethod('put') && $this->routeIs('update.module.category'))
        {
            return [
                'id' =>[
                    'required',
                        'numeric',
                            Rule::exists('module_categories', 'id')->whereNull('deleted_at')
                ],
                'name' =>[
                    'required',
                        'max:255'
                ]
            ];
        }

        if($this->isMethod('post') && $this->routeIs('create.module.category'))
        {
            return [
                'name' =>[
                    'required',
                        'max:255'
                ]
            ];
        }
    }

    public function messages()
    {
        return [
            'name.required' => 'ກະລຸນາປ້ອນຊື່ກ່ອນ...',
            'name.max' => 'ຊື່ບໍ່ຄວນເກີນ 255 ຕົວອັກສອນ...',

            'id.required' => 'ກະລຸນາປ້ອນ id ກ່ອນ...',
            'id.numeric' => 'id ຄວນເປັນໂຕເລກ...',
            'id.exists' => 'id ບໍ່ມີໃນລະບົບ...',

        ];
    }
}
