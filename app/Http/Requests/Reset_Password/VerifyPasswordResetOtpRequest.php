<?php

namespace App\Http\Requests\Reset_Password;

use App\Http\Requests\ApiFormRequest;

class VerifyPasswordResetOtpRequest extends ApiFormRequest
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
            'email' => ['required', 'string', 'email:rfc'],
            'otp_code' => ['required', 'digits:6'],
        ];

    }

    public function messages(): array
    {
        return [
            'email.required' => 'حقل البريد الإلكتروني مطلوب',
            'email.string' => 'حقل البريد الإلكتروني غير صالح',
            'email.email' => 'صيغة البريد الإلكتروني غير صحيحة',

            'otp_code.required' => 'رمز التحقق مطلوب',
            'otp_code.digits' => 'رمز التحقق يجب أن يتكون من 6 أرقام',
        ];
    }
}
