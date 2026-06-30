<?php

namespace App\Http\Requests\Settings;

use App\Http\Requests\ApiFormRequest;

class UpdateCertificateVisibilityRequest extends ApiFormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'show_certificate_publicly' => [
                'required',
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'show_certificate_publicly.required' => 'حالة ظهور الشهادة مطلوبة',
            'show_certificate_publicly.boolean' => 'حالة ظهور الشهادة غير صالحة',
        ];
    }
}
