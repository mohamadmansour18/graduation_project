<?php

namespace App\Http\Requests\StudyPlans;

use App\Http\Requests\ApiFormRequest;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\Validator;

class StoreStudyPlanRequest extends ApiFormRequest
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
            'title' => ['required', 'string', 'min:10', 'max:100'],
            'emoji' => ['required', 'string', 'max:20'],

            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after:start_date'],

            'subject_ids' => ['required', 'array', 'min:1', 'max:10'],
            'subject_ids.*' => ['required', 'integer', 'distinct'],

            'daily_study_hours' => ['required', 'integer', 'min:1', 'max:12'],

            'is_default' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $today = today();
            $maxFutureStartDate = today()->addMonths(6);

            $startDate = Carbon::createFromFormat('Y-m-d', $this->input('start_date'))->startOfDay();
            $endDate = Carbon::createFromFormat('Y-m-d', $this->input('end_date'))->startOfDay();

            if ($startDate->lt($today)) {
                $validator->errors()->add(
                    'start_date',
                    'لا يمكن إنشاء خطة دراسية بتاريخ بداية سابق لليوم'
                );
            }

            if ($startDate->gt($maxFutureStartDate)) {
                $validator->errors()->add(
                    'start_date',
                    'لا يمكن أن تبدأ الخطة الدراسية بعد أكثر من ستة أشهر من تاريخ اليوم'
                );
            }

            if ($startDate->diffInDays($endDate) < 1) {
                $validator->errors()->add(
                    'end_date',
                    'يجب أن تكون مدة الخطة الدراسية يومًا واحدًا على الأقل'
                );
            }

            if ($startDate->diffInDays($endDate) > 365) {
                $validator->errors()->add(
                    'end_date',
                    'لا يمكن أن تكون مدة الخطة الدراسية أكثر من سنة واحدة'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'title.required' => 'عنوان الخطة الدراسية مطلوب',
            'title.string' => 'عنوان الخطة الدراسية يجب أن يكون نصًا',
            'title.min' => 'عنوان الخطة الدراسية يجب ألا يقل عن 10 أحرف',
            'title.max' => 'عنوان الخطة الدراسية يجب ألا يتجاوز 100 حرف',

            'emoji.required' => 'إيموجي الخطة مطلوب',
            'emoji.string' => 'إيموجي الخطة يجب أن يكون نصًا',
            'emoji.max' => 'إيموجي الخطة غير صالح',

            'start_date.required' => 'تاريخ بداية الخطة مطلوب',
            'start_date.date_format' => 'صيغة تاريخ بداية الخطة يجب أن تكون Y-m-d',

            'end_date.required' => 'تاريخ نهاية الخطة مطلوب',
            'end_date.date_format' => 'صيغة تاريخ نهاية الخطة يجب أن تكون Y-m-d',
            'end_date.after' => 'تاريخ نهاية الخطة يجب أن يكون بعد تاريخ البداية',

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

            'is_default.boolean' => 'قيمة جعل الخطة افتراضية غير صالحة',
        ];
    }

}
