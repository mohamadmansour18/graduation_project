<?php

namespace App\Http\Requests\Onboarding;

use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;

class SaveUserInterestsRequest extends ApiFormRequest
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

            'interest_ids' => ['required', 'array', 'min:1', 'max:5'],
            'interest_ids.*' => ['required', 'integer', 'distinct' , Rule::exists('interests', 'id')],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'حقل البريد الإلكتروني مطلوب',
            'email.exists' => 'البريد الالكتروني الذي تحاول الوصول اليه غير موجود',

            'interest_ids.required' => 'يرجى اختيار الاهتمامات العلمية',
            'interest_ids.array' => 'صيغة الاهتمامات العلمية غير صحيحة',
            'interest_ids.min' => 'يجب اختيار اهتمام علمي واحد على الأقل',
            'interest_ids.max' => 'لا يمكن اختيار أكثر من 5 اهتمامات علمية',

            'interest_ids.*.required' => 'معرّف الاهتمام مطلوب',
            'interest_ids.*.integer' => 'معرّف الاهتمام غير صالح',
            'interest_ids.*.distinct' => 'لا يمكن اختيار نفس الاهتمام أكثر من مرة',
            'interest_ids.*.exists' => 'أحد الاهتمامات العلمية المحددة غير موجود',
        ];
    }
}
