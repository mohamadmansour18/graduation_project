<?php

namespace App\Http\Requests\Admin;

use App\Enums\Gender;
use App\Enums\Governorate;
use App\Http\Requests\ApiFormRequest;
use App\Rules\ArabicOnly;
use Illuminate\Validation\Rule;

class UpdateDashboardProfileRequest extends ApiFormRequest
{
    protected $stopOnFirstFailure = true;
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'min:2', 'max:100' , new ArabicOnly()],

            'governorate' => [
                'sometimes',
                'string',
                Rule::enum(Governorate::class),
            ],

            'phone' => [
                'sometimes',
                'regex:/^09\d{8}$/',
                Rule::unique('user_profile', 'phone')->ignore($this->user()->userProfile?->id),
            ],

            'gender' => ['sometimes', Rule::enum(Gender::class)],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (empty($this->validated())) {
                $validator->errors()->add(
                    'fields',
                    'يجب إرسال حقل واحد على الأقل للتعديل'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'name.min' => 'الاسم يجب ألا يقل عن محرفين',
            'name.max' => 'الاسم طويل جداً',
            'name.arabic_only' => 'يجب أن يحتوي الاسم على أحرف عربية فقط',

            'governorate.Illuminate\Validation\Rules\Enum' => 'المحافظة غير صالحة',

            'phone.regex' => 'رقم الهاتف يجب أن يبدأ بـ 09 ويتكون من 10 أرقام',
            'phone.unique' => 'رقم الهاتف مستخدم بالفعل',

            'gender.Illuminate\Validation\Rules\Enum' => 'الجنس غير صالح',
        ];
    }
}
