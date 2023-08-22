<?php

namespace App\Http\Requests\moduleCategory;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class ModuleTitleRequest extends FormRequest
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
        if($this->isMethod('put') && $this->routeIs('update.module.title')
             || $this->isMethod('delete') && $this->routeIs('delete.module.title')

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
        if($this->isMethod('delete') && $this->routeIs('delete.module.title'))
        {
            return [
                'id' =>[
                    'required',
                        'numeric',
                            Rule::exists('module_titles', 'id')->whereNull('deleted_at')
                ],
            ];
        }

        if($this->isMethod('put') && $this->routeIs('update.module.title'))
        {
            return [
                'id' =>[
                    'required',
                        'numeric',
                            Rule::exists('module_titles', 'id')->whereNull('deleted_at')
                ],
                'module_category_id' =>[
                    'required',
                        'numeric',
                            Rule::exists('module_categories', 'id')->whereNull('deleted_at')
                ],
                'name' =>[
                    'required',
                        'max:255'
                ],
                'hour' =>[
                    'required',
                        'numeric',
                ]
            ];
        }

        if($this->isMethod('post') && $this->routeIs('create.module.title'))
        {
            return [
                'module_category_id' =>[
                    'required',
                        'numeric',
                            Rule::exists('module_categories', 'id')->whereNull('deleted_at')
                ],
                'name' =>[
                    'required',
                        'max:255'
                ],
                'hour' =>[
                    'required',
                        'numeric',
                ]
            ];
        }
    }

    public function messages()
    {
        return [
            'module_category_id.required' => 'ກະລຸນາປ້ອນ id ກ່ອນ...',
            'module_category_id.numeric' => 'id ຄວນເປັນໂຕເລກ...',
            'module_category_id.exists' => 'id ບໍ່ມີໃນລະບົບ...',

            'id.required' => 'ກະລຸນາປ້ອນ id ກ່ອນ...',
            'id.numeric' => 'id ຄວນເປັນໂຕເລກ...',
            'id.exists' => 'id ບໍ່ມີໃນລະບົບ...',

            'name.required' => 'ກະລຸນາປ້ອນຊື່ກ່ອນ...',
            'name.max' => 'ຊື່ບໍ່ຄວນເກີນ 255 ຕົວອັກສອນ...',

            'hour.required' => 'ກະລຸນາປ້ອນຊົ່ວໂມງກ່ອນ...',
            'hour.numeric' => 'ຊົ່ວໂມງກ່ອນຄວນເປັນໂຕເລກ...',
        ];
    }
}
