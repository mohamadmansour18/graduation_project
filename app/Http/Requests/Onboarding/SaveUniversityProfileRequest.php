<?php

namespace App\Http\Requests\Onboarding;

use App\Enums\UniversityDepartment;
use App\Enums\UniversityName;
use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rules\Enum;

class SaveUniversityProfileRequest extends ApiFormRequest
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

            'university_year' => [
                'required',
                'in:1,2,3,4,5,6'
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

            'university_year.required' => 'حقل السنة الدراسية مطلوب',
            'university_year.' . Enum::class => 'السنة الدراسية المحددة غير صالحة',
        ];
    }
}
