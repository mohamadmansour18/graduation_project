<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\ApiFormRequest;

class LoginRequest extends ApiFormRequest
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
            'email' => ['required', 'string', 'email:rfc' ],
            'password' => ['required', 'string' , 'min:8'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'حقل البريد الإلكتروني مطلوب',
            'email.string' => 'حقل البريد الإلكتروني غير صالح',
            'email.email' => 'صيغة البريد الإلكتروني غير صحيحة',
            'password.required' => 'كلمة المرور مطلوبة',
            'password.string' => 'كلمة المرور غير صالحة',
            'password.min' => 'كلمة المرور غير صحيحة',
        ];
    }
}
