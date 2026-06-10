<?php

namespace App\Http\Requests\Profile;

use App\Http\Requests\ApiFormRequest;

class UpdateScientificInterestsRequest extends ApiFormRequest
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
            'interest_ids' => ['required', 'array', 'min:1', 'max:5'],
            'interest_ids.*' => ['integer', 'distinct', 'exists:interests,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'interest_ids.required' => 'يجب اختيار اهتمام علمي واحد على الأقل',
            'interest_ids.array' => 'صيغة الاهتمامات غير صحيحة',
            'interest_ids.min' => 'يجب اختيار اهتمام علمي واحد على الأقل',
            'interest_ids.max' => 'لا يمكنك اختيار أكثر من خمسة اهتمامات',
            'interest_ids.*.exists' => 'أحد الاهتمامات المختارة غير موجود',
            'interest_ids.*.distinct' => 'لا يمكن تكرار نفس الاهتمام أكثر من مرة',
        ];
    }
}
