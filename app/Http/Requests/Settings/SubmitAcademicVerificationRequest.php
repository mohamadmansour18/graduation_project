<?php

namespace App\Http\Requests\Settings;

use App\Http\Requests\ApiFormRequest;

class SubmitAcademicVerificationRequest extends ApiFormRequest
{
    protected $stopOnFirstFailure = true;
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'certificate_image' => [
                'required',
                'image',
                'file',
                'mimes:jpg,jpeg,png,webp',
                'max:4096',
            ],
            'identity_image' => [
                'required',
                'image',
                'file',
                'mimes:jpg,jpeg,png,webp',
                'max:4096',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'certificate_image.required' => 'صورة الشهادة الجامعية مطلوبة',
            'certificate_image.image' => 'ملف الشهادة يجب أن يكون صورة',
            'certificate_image.mimes' => 'صيغة صورة الشهادة غير صحيحة',
            'certificate_image.max' => 'حجم صورة الشهادة يجب ألا يتجاوز 4MB',
            'certificate_image.file' => 'ملف الشهادة غير صالح',

            'identity_image.required' => 'صورة الهوية الشخصية مطلوبة',
            'identity_image.image' => 'ملف الهوية يجب أن يكون صورة',
            'identity_image.mimes' => 'صيغة صورة الهوية غير صحيحة',
            'identity_image.max' => 'حجم صورة الهوية يجب ألا يتجاوز 4MB',
            'identity_image.file' => 'ملف الهوية غير صالح',
        ];
    }
}
