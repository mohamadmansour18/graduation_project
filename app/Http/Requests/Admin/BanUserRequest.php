<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\ApiFormRequest;

class BanUserRequest extends ApiFormRequest
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
            'is_permanent' => ['required', 'in:0,1'],

            'starts_at' => [
                'prohibited_if:is_permanent,1',
                'required_if:is_permanent,0',
                'nullable',
                'date',
                'after_or_equal:today',
            ],

            'ends_at' => [
                'prohibited_if:is_permanent,1',
                'required_if:is_permanent,0',
                'nullable',
                'date',
                'after:starts_at',
            ],

            'reason' => ['required', 'string', 'min:10', 'max:250'],
        ];
    }

    public function messages(): array
    {
        return [
            'is_permanent.required' => 'نوع الحظر مطلوب',
            'is_permanent.in' => 'نوع الحظر غير صالح',

            'starts_at.prohibited_if' => 'لا يمكن إرسال تاريخ بداية الحظر عند اختيار حظر دائم',
            'starts_at.required_if' => 'تاريخ بداية الحظر مطلوب في الحظر المؤقت',
            'starts_at.date' => 'تاريخ بداية الحظر غير صالح',
            'starts_at.after_or_equal' => 'تاريخ بداية الحظر يجب ألا يكون في الماضي',

            'ends_at.prohibited_if' => 'لا يمكن إرسال تاريخ نهاية الحظر عند اختيار حظر دائم',
            'ends_at.required_if' => 'تاريخ نهاية الحظر مطلوب في الحظر المؤقت',
            'ends_at.date' => 'تاريخ نهاية الحظر غير صالح',
            'ends_at.after' => 'تاريخ نهاية الحظر يجب أن يكون بعد تاريخ البداية',

            'reason.required' => 'سبب الحظر مطلوب',
            'reason.min' => 'سبب الحظر يجب ألا يقل عن 10 محارف',
            'reason.max' => 'سبب الحظر طويل جداً',
        ];
    }
}
