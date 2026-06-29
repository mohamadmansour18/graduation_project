<?php

namespace App\Http\Requests\StudyPlans;

use App\Enums\Priority;
use App\Enums\RepeatPattern;
use App\Http\Requests\ApiFormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

class UpdateStudyTaskRequest extends ApiFormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'min:4', 'max:100'],
            'description' => ['sometimes', 'required', 'string', 'min:3', 'max:1000'],

            'study_plan_subject_id' => ['sometimes', 'required', 'integer'],

            'start_date' => ['sometimes', 'required', 'date_format:Y-m-d'],
            'end_date' => ['sometimes', 'required', 'date_format:Y-m-d'],
            'start_time' => ['sometimes', 'required', 'date_format:H:i'],
            'duration_minutes' => ['sometimes', 'required', 'integer', 'min:10', 'max:720'],

            'priority' => ['sometimes', 'required', 'string', Rule::enum(Priority::class)],

            'repeat_pattern' => [
                'sometimes',
                'nullable',
                'string',
                Rule::enum(RepeatPattern::class),
            ],

            'repeat_weekday' => ['sometimes', 'nullable', 'integer', 'between:0,6'],

            'reminder_offset_minutes' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::in([0, 5, 15, 30, 45, 60, 120, 240, 720, 1440, 2880, 10080]),
            ],

            /**
             * إذا أرسل الفرونت subtasks فنعتبرها sync كامل:
             * - id موجود = تحديث مهمة فرعية موجودة
             * - بدون id = إنشاء مهمة فرعية جديدة
             * - أي subtask قديمة غير مرسلة سيتم حذفها
             * - إرسال [] يعني حذف كل المهام الفرعية
             */
            'subtasks' => ['sometimes', 'array', 'max:20'],
            'subtasks.*.id' => ['sometimes', 'integer'],
            'subtasks.*.title' => ['required_with:subtasks', 'string', 'min:2', 'max:150'],
            'subtasks.*.is_completed' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if (empty($this->validated())) {
                $validator->errors()->add('payload', 'يجب إرسال حقل واحد على الأقل للتعديل');
                return;
            }

            $repeatPattern = $this->input('repeat_pattern');

            if ($repeatPattern && $repeatPattern !== 'none' && ! $this->has('repeat_weekday')) {
                $validator->errors()->add('repeat_weekday', 'يجب اختيار يوم التكرار عند تفعيل التكرار');
            }
        });
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('title')) {
            $this->merge(['title' => trim((string) $this->input('title'))]);
        }

        if ($this->has('description')) {
            $this->merge(['description' => trim((string) $this->input('description'))]);
        }
    }

    public function messages(): array
    {
        return [
            'title.required' => 'عنوان المهمة مطلوب',
            'title.min' => 'عنوان المهمة يجب ألا يقل عن 4 أحرف',
            'title.max' => 'عنوان المهمة يجب ألا يتجاوز 100 حرفًا',

            'description.required' => 'وصف المهمة مطلوب',
            'description.min' => 'وصف المهمة يجب ألا يقل عن 3 أحرف',
            'description.max' => 'وصف المهمة يجب ألا يتجاوز 1000 حرف',

            'study_plan_subject_id.required' => 'مادة المهمة مطلوبة',
            'study_plan_subject_id.integer' => 'معرف مادة المهمة غير صالح',

            'start_date.required' => 'تاريخ بداية المهمة مطلوب',
            'start_date.date_format' => 'صيغة تاريخ بداية المهمة يجب أن تكون Y-m-d',

            'end_date.required' => 'تاريخ نهاية المهمة مطلوب',
            'end_date.date_format' => 'صيغة تاريخ نهاية المهمة يجب أن تكون Y-m-d',

            'start_time.required' => 'وقت بداية المهمة مطلوب',
            'start_time.date_format' => 'صيغة وقت بداية المهمة يجب أن تكون H:i',

            'duration_minutes.required' => 'مدة المهمة مطلوبة',
            'duration_minutes.integer' => 'مدة المهمة يجب أن تكون رقمًا صحيحًا بالدقائق',
            'duration_minutes.min' => 'مدة المهمة يجب ألا تقل عن 10 دقائق',
            'duration_minutes.max' => 'مدة المهمة يجب ألا تتجاوز 12 ساعة',

            'priority.Illuminate\Validation\Rules\Enum' => 'أولوية المهمة غير صالحة',

            'repeat_pattern.Illuminate\Validation\Rules\Enum' => 'نمط التكرار غير صالح',
            'repeat_weekday.between' => 'يوم التكرار غير صالح',

            'reminder_offset_minutes.in' => 'قيمة تذكير المهمة غير صالحة',

            'subtasks.array' => 'المهام الفرعية يجب أن تكون مصفوفة',
            'subtasks.max' => 'لا يمكن إضافة أكثر من 20 مهمة فرعية',
            'subtasks.*.id.integer' => 'معرف المهمة الفرعية غير صالح',
            'subtasks.*.title.required_with' => 'عنوان المهمة الفرعية مطلوب',
            'subtasks.*.title.min' => 'عنوان المهمة الفرعية يجب ألا يقل عن 4 محارف',
            'subtasks.*.title.max' => 'عنوان المهمة الفرعية يجب ألا يتجاوز 100 حرفًا',
            'subtasks.*.is_completed.boolean' => 'حالة إنجاز المهمة الفرعية غير صالحة',
        ];
    }
}
