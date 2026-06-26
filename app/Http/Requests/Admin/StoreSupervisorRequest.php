<?php

namespace App\Http\Requests\Admin;

use App\Enums\Gender;
use App\Http\Requests\ApiFormRequest;
use App\Rules\AllowedEmailDomain;
use App\Rules\ArabicOnly;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreSupervisorRequest extends ApiFormRequest
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
            'name' => ['required', 'string', 'min:2', 'max:100' , new ArabicOnly()],

            'email' => [
                'required',
                'email:rfc',
                'max:255',
                Rule::unique('users', 'email'),
                new AllowedEmailDomain()
            ],

            'governorate' => [
                'required',
                'string',
                Rule::enum(\App\Enums\Governorate::class),
            ],

            'phone' => ['required', 'regex:/^09\d{8}$/', Rule::unique('user_profile', 'phone'),],

            'gender' => ['required', Rule::enum(Gender::class)],

            'password' => [
                'required',
                'string',
                Password::min(6)->letters()->numbers(),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'اسم المشرف مطلوب',
            'name.min' => 'اسم المشرف يجب ألا يقل عن محرفين',
            'name.max' => 'اسم المشرف طويل جداً',
            'name.arabic_only' => 'يجب أن يحتوي الاسم على أحرف عربية فقط',

            'email.required' => 'البريد الإلكتروني مطلوب',
            'email.email' => 'صيغة البريد الإلكتروني غير صحيحة',
            'email.unique' => 'البريد الإلكتروني مستخدم بالفعل',

            'governorate.required' => 'المحافظة مطلوبة',
            'governorate.Illuminate\Validation\Rules\Enum' => 'المحافظة غير صالحة',

            'phone.required' => 'رقم الهاتف مطلوب',
            'phone.regex' => 'رقم الهاتف يجب أن يبدأ بـ 09 ويتكون من 10 أرقام',
            'phone.unique' => 'رقم الهاتف مستخدم بالفعل',

            'gender.required' => 'حقل الجنس مطلوب',
            'gender.Illuminate\Validation\Rules\Enum' => 'الجنس غير صالح',

            'password.required' => 'كلمة المرور مطلوبة',
            'password.min' => 'كلمة المرور يجب ألا تقل عن 6 محارف',
            'password.letters' => 'كلمة المرور يجب أن تحتوي على أحرف',
            'password.numbers' => 'كلمة المرور يجب أن تحتوي على أرقام',
        ];
    }
}
