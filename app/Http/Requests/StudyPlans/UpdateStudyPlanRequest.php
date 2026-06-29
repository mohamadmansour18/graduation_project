<?php

namespace App\Http\Requests\StudyPlans;

use App\Http\Requests\ApiFormRequest;

class UpdateStudyPlanRequest extends ApiFormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'min:3', 'max:100'],
            'emoji' => ['sometimes', 'required', 'string', 'max:20'],

            'start_date' => ['sometimes', 'required', 'date_format:Y-m-d'],
            'end_date' => ['sometimes', 'required', 'date_format:Y-m-d'],

            'subject_ids' => ['sometimes', 'required', 'array', 'min:1', 'max:10'],
            'subject_ids.*' => ['required', 'integer', 'distinct'],

            'daily_study_hours' => ['sometimes', 'required', 'integer', 'min:1', 'max:12'],

            'is_default' => ['sometimes', 'required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('title')) {
            $this->merge([
                'title' => trim((string) $this->input('title')),
            ]);
        }
    }

    public function messages(): array
    {
        return [
            'title.required' => 'عنوان الخطة الدراسية مطلوب',
            'title.string' => 'عنوان الخطة الدراسية يجب أن يكون نصًا',
            'title.min' => 'عنوان الخطة الدراسية يجب ألا يقل عن 3 أحرف',
            'title.max' => 'عنوان الخطة الدراسية يجب ألا يتجاوز 100 حرف',

            'emoji.required' => 'إيموجي الخطة مطلوب',
            'emoji.string' => 'إيموجي الخطة يجب أن يكون نصًا',
            'emoji.max' => 'إيموجي الخطة غير صالح',

            'start_date.required' => 'تاريخ بداية الخطة مطلوب',
            'start_date.date_format' => 'صيغة تاريخ بداية الخطة يجب أن تكون Y-m-d',

            'end_date.required' => 'تاريخ نهاية الخطة مطلوب',
            'end_date.date_format' => 'صيغة تاريخ نهاية الخطة يجب أن تكون Y-m-d',

            'subject_ids.required' => 'مواد الخطة الدراسية مطلوبة',
            'subject_ids.array' => 'مواد الخطة الدراسية يجب أن تكون مصفوفة',
            'subject_ids.min' => 'يجب اختيار مادة واحدة على الأقل',
            'subject_ids.max' => 'لا يمكن اختيار أكثر من عشر مواد للخطة الواحدة',

            'subject_ids.*.integer' => 'معرف المادة غير صالح',
            'subject_ids.*.distinct' => 'لا يمكن تكرار نفس المادة ضمن الخطة',

            'daily_study_hours.required' => 'عدد ساعات الدراسة اليومية مطلوب',
            'daily_study_hours.integer' => 'عدد ساعات الدراسة اليومية يجب أن يكون رقمًا صحيحًا',
            'daily_study_hours.min' => 'أقل عدد ساعات دراسة يومية هو ساعة واحدة',
            'daily_study_hours.max' => 'أكثر عدد ساعات دراسة يومية هو 12 ساعة',

            'is_default.required' => 'قيمة جعل الخطة افتراضية مطلوبة',
            'is_default.boolean' => 'قيمة جعل الخطة افتراضية غير صالحة',
        ];
    }
}
