<?php

namespace App\Http\Requests\Profile;

use App\Enums\EducationLevel;
use App\Enums\SchoolStage;
use App\Enums\UniversityDepartment;
use App\Enums\UniversityName;
use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;

class UpdateAcademicInformationRequest extends ApiFormRequest
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
            'education_level' => [
                'required',
                'string',
                Rule::enum(EducationLevel::class),
            ],

            'school_stage' => [
                'sometimes',
                'nullable',
                'string',
                Rule::enum(SchoolStage::class),
            ],

            'university_name' => [
                'sometimes',
                'nullable',
                'string',
                Rule::enum(UniversityName::class),
            ],

            'department' => [
                'sometimes',
                'nullable',
                'string',
                Rule::enum(UniversityDepartment::class)
            ],

            'university_year' => [
                'sometimes',
                'nullable',
                'string',
                'in:1,2,3,4,5,6'
            ],

            'certificate_image' => [
                'nullable',
                'required_with:identity_image',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:4096',
            ],

            'identity_image' => [
                'nullable',
                'required_with:certificate_image',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:4096',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'education_level.required' => 'المستوى الدراسي مطلوب',
            'education_level.Illuminate\Validation\Rules\Enum' => 'المستوى الدراسي غير صحيح',

            'school_stage.Illuminate\Validation\Rules\Enum' => 'المرحلة الدراسية غير صحيحة',

            'university_name.Illuminate\Validation\Rules\Enum' => 'اسم الجامعة المختار غير صالح',
            'department.Illuminate\Validation\Rules\Enum' => 'القسم الجامعي المختار غير صالح',
            'university_year.in' => 'السنة الجامعية المختارة غير صالحة',

            'certificate_image.required_with' => 'يجب إرسال صورة الشهادة وصورة الهوية معًا',
            'identity_image.required_with' => 'يجب إرسال صورة الهوية وصورة الشهادة معًا',

            'certificate_image.image' => 'صورة الشهادة يجب أن تكون صورة صحيحة',
            'identity_image.image' => 'صورة الهوية يجب أن تكون صورة صحيحة',

            'certificate_image.mimes' => 'صيغة صورة الشهادة غير صحيحة',
            'identity_image.mimes' => 'صيغة صورة الشهادة غير صحيحة',

            'certificate_image.max' => 'حجم صورة الشهادة يجب ألا يتجاوز 4 ميغابايت',
            'identity_image.max' => 'حجم صورة الهوية يجب ألا يتجاوز 4 ميغابايت'
        ];
    }
}
