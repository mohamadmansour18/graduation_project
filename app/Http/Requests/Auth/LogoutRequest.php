<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\ApiFormRequest;

class LogoutRequest extends ApiFormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'fcm_token' => ['nullable', 'string'],
            'device_id' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'fcm_token.string' => 'رمز الإشعارات غير صالح',

            'device_id.string' => 'معرّف الجهاز غير صالح',
            'device_id.max' => 'معرّف الجهاز طويل جدًا',
        ];
    }
}
