<?php

namespace App\Http\Requests\Onboarding;

use App\Enums\UniversityDepartment;
use App\Enums\UniversityName;
use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rules\Enum;

class SaveGraduateAcademicProfileRequest extends ApiFormRequest
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
            'email' => ['required', 'exists:users,email'],

            'university_name' => [
                'required',
                new Enum(UniversityName::class),
            ],

            'department' => [
                'required',
                new Enum(UniversityDepartment::class)
            ],

            'certificate_image' => [
                'nullable',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
                'required_with:identity_image',
            ],

            'identity_image' => [
                'nullable',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
                'required_with:certificate_image',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'حقل البريد الإلكتروني مطلوب',
            'email.exists' => 'البريد الالكتروني الذي تحاول الوصول اليه غير موجود',

            'university_name.required' => 'حقل الجامعة مطلوب',
            'university_name.' . Enum::class => 'الجامعة المحددة غير صالحة',

            'department.required' => 'حقل القسم مطلوب',
            'department.' . Enum::class => 'القسم المحدد غير صالح',

            'certificate_image.file' => 'ملف الشهادة غير صالح',
            'certificate_image.image' => 'يجب أن تكون صورة الشهادة ملف صورة صالحًا',
            'certificate_image.mimes' => 'صيغة صورة الشهادة غير صحيحة',
            'certificate_image.max' => 'حجم صورة الشهادة يجب ألا يتجاوز 5 ميغابايت',
            'certificate_image.required_with' => 'حقل صورة الشهادة مطلوب عندما يتم تقديم صورة الهوية',

            'identity_image.file' => 'ملف الهوية غير صالح',
            'identity_image.image' => 'يجب أن تكون صورة الهوية ملف صورة صالحًا',
            'identity_image.mimes' => 'صيغة صورة الهوية غير صحيحة',
            'identity_image.max' => 'حجم صورة الهوية يجب ألا يتجاوز 5 ميغابايت',
            'identity_image.required_with' => 'حقل صورة الهوية مطلوب عندما يتم تقديم صورة الشهادة',

        ];
    }
}
