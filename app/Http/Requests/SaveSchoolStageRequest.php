<?php

namespace App\Http\Requests;

use App\Enums\SchoolStage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class SaveSchoolStageRequest extends ApiFormRequest
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
            'school_stage' => [
                'required',
                new Enum(SchoolStage::class),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'حقل البريد الإلكتروني مطلوب',
            'email.exists' => 'البريد الالكتروني الذي تحاول الوصول اليه غير موجود',

            'school_stage.required' => 'يرجى اختيار المرحلة الدراسية',
            'school_stage.' . Enum::class => 'المرحلة الدراسية المحددة غير صالحة'
        ];
    }
}
