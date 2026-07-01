<?php

namespace App\Http\Requests\FCM;

use App\Http\Requests\ApiFormRequest;

class UpsertFcmTokenRequest extends ApiFormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fcm_token' => ['required', 'string'],
            'device_id' => ['nullable', 'string', 'max:255'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'fcm_token.required' => 'رمز الإشعارات مطلوب',
            'fcm_token.string' => 'رمز الإشعارات غير صالح',

            'device_id.string' => 'معرّف الجهاز غير صالح',
            'device_id.max' => 'معرّف الجهاز طويل جدًا',

            'device_name.string' => 'اسم الجهاز غير صالح',
            'device_name.max' => 'اسم الجهاز طويل جدًا',
        ];
    }
}
