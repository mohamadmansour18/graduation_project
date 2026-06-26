<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rules\Password;

class UpdateDashboardPasswordRequest extends ApiFormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'old_password' => ['required', 'string'],

            'new_password' => [
                'required',
                'string',
                'confirmed',
                'different:old_password',
                Password::min(6)->letters()->numbers(),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'old_password.required' => 'كلمة المرور القديمة مطلوبة',

            'new_password.required' => 'كلمة المرور الجديدة مطلوبة',
            'new_password.confirmed' => 'تأكيد كلمة المرور الجديدة غير مطابق',
            'new_password.different' => 'كلمة المرور الجديدة يجب أن تكون مختلفة عن القديمة',
            'new_password.min' => 'كلمة المرور الجديدة يجب ألا تقل عن 6 محارف',
            'new_password.letters' => 'كلمة المرور الجديدة يجب أن تحتوي على أحرف',
            'new_password.numbers' => 'كلمة المرور الجديدة يجب أن تحتوي على أرقام',
        ];
    }
}
