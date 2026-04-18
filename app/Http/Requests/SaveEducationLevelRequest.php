<?php

namespace App\Http\Requests;

use App\Enums\EducationLevel;
use App\Enums\Governorate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class SaveEducationLevelRequest extends ApiFormRequest
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
            'email' => ['required' , 'exists:users,email'],
            'governorate' => [
                'required',
                new Enum(Governorate::class),
            ],

            'education_level' => [
                'required',
                new Enum(EducationLevel::class),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'حقل البريد الإلكتروني مطلوب',
            'email.exists' => 'البريد الالكتروني الذي تحاول الوصول اليه غير موجود',

            'governorate.required' => 'يرجى اختيار المحافظة',
            'governorate.' . Enum::class => 'المحافظة المحددة غير صالحة',

            'education_level.required' => 'يرجى اختيار المستوى الدراسي',
            'education_level.' . Enum::class => 'المستوى الدراسي المحدد غير صالح',
        ];
    }
}
