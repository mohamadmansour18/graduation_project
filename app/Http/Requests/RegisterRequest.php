<?php

namespace App\Http\Requests;

use App\Enums\Gender;
use App\Rules\AllowedEmailDomain;
use App\Rules\ArabicOnly;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Password;
class RegisterRequest extends ApiFormRequest
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
            'name' => ['required', 'string', 'min:2', 'max:255' , new ArabicOnly()],
            'email' => ['required', 'string', 'unique:users,email' , 'email:rfc', 'max:255' , new AllowedEmailDomain()],
            'password' => [
                'required',
                'string',
                Password::min(8)->letters()->mixedCase()->numbers(),
            ],
            'gender' => ['required', new Enum(Gender::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'حقل الأسم مطلوب',
            'name.string' => 'حقل الأسم غير صالح',
            'name.min' => 'يجب ان يتكون الأسم من حرفين على الأقل',
            'name.max' => 'الاسم طويل جد',
            'name.arabic_only' => 'يجب أن يحتوي الاسم على أحرف عربية فقط',

            'email.required' => 'حقل البريد الإلكتروني مطلوب',
            'email.string' => 'حقل البريد الإلكتروني غير صالح',
            'email.email' => 'صيغة البريد الإلكتروني غير صحيحة',
            'email.max' => 'البريد الإلكتروني طويل جدًا',
            'email.unique' => 'البريد الإلكتروني مستخدم بالفعل يرجى استخدام بريد إلكتروني آخر',

            'password.required' => 'حقل كلمة المرور مطلوب',
            'password.string' => 'حقل كلمة المرور غير صالح',
            'password.confirmed' => 'تأكيد كلمة المرور غير متطابق',
            'password.min' => 'يجب أن تتكون كلمة المرور من 8 أحرف على الأقل',

            'gender.required' => 'حقل الجنس مطلوب',
            'gender.' . Enum::class => 'قيمة حقل الجنس يجب أن تكون ذكر أو أنثى فقط',
        ];
    }
}
