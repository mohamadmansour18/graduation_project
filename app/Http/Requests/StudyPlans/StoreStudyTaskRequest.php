<?php

namespace App\Http\Requests\StudyPlans;

use App\Enums\Priority;
use App\Enums\RepeatPattern;
use App\Http\Requests\ApiFormRequest;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

class StoreStudyTaskRequest extends ApiFormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:4', 'max:100'],
            'description' => ['required', 'string', 'min:10', 'max:250'],

            'study_plan_subject_id' => ['required', 'integer'],

            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d'],

            'start_time' => ['required', 'date_format:H:i'],

            'duration_minutes' => ['required', 'integer', 'min:10', 'max:720'],

            'priority' => ['required', 'string', Rule::enum(Priority::class)],

            'subtasks' => ['sometimes', 'array', 'max:20'],
            'subtasks.*.title' => ['required', 'string', 'min:4', 'max:100', 'distinct'],

            'repeat_pattern' => [
                'sometimes',
                'nullable',
                'string',
                Rule::enum(RepeatPattern::class),
            ],

            'repeat_weekday' => ['required_unless:repeat_pattern,null,none', 'integer', 'between:0,6'],

            'reminder_offset_minutes' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::in([0, 5, 15, 30, 45, 60, 120, 240, 720, 1440, 2880, 10080]),
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $startDate = Carbon::createFromFormat('Y-m-d', $this->input('start_date'))->startOfDay();
            $endDate = Carbon::createFromFormat('Y-m-d', $this->input('end_date'))->startOfDay();

            if ($endDate->lt($startDate)) {
                $validator->errors()->add('end_date', 'تاريخ انتهاء المهمة يجب أن يكون بعد أو يساوي تاريخ البداية');
                return;
            }

            if ($startDate->lt(today())) {
                $validator->errors()->add('start_date', 'لا يمكن إنشاء مهمة بتاريخ بداية سابق لليوم');
                return;
            }

            if ($startDate->diffInDays($endDate) > 7) {
                $validator->errors()->add('end_date', 'لا يمكن أن تمتد المهمة لأكثر من أسبوع واحد');
                return;
            }

            $startDateTime = Carbon::parse($this->input('start_date') . ' ' . $this->input('start_time'));
            $endDateTime = $startDateTime->copy()->addMinutes((int) $this->input('duration_minutes'));

            if (! $endDateTime->isSameDay($startDateTime)) {
                $validator->errors()->add(
                    'duration_minutes',
                    'مدة المهمة مع وقت البداية يجب ألا تتجاوز نهاية اليوم'
                );
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

        if (! $this->has('repeat_pattern')) {
            $this->merge(['repeat_pattern' => RepeatPattern::None->value]);
        }
    }

    public function messages(): array
    {
        return [
            'title.required' => 'عنوان المهمة مطلوب',
            'title.min' => 'عنوان المهمة يجب ألا يقل عن 4 أحرف',
            'title.max' => 'عنوان المهمة يجب ألا يتجاوز 100 حرفًا',

            'description.required' => 'وصف المهمة مطلوب',
            'description.min' => 'وصف المهمة يجب ألا يقل عن 10 أحرف',
            'description.max' => 'وصف المهمة يجب ألا يتجاوز 250 حرف',

            'study_plan_subject_id.required' => 'مادة المهمة مطلوبة',
            'study_plan_subject_id.integer' => 'معرف مادة المهمة غير صالح',

            'start_date.required' => 'تاريخ بداية المهمة مطلوب',
            'start_date.date_format' => 'صيغة تاريخ بداية المهمة يجب أن تكون Y-m-d',

            'end_date.required' => 'تاريخ انتهاء المهمة مطلوب',
            'end_date.date_format' => 'صيغة تاريخ انتهاء المهمة يجب أن تكون Y-m-d',

            'start_time.required' => 'وقت بداية المهمة مطلوب',
            'start_time.date_format' => 'صيغة وقت بداية المهمة يجب أن تكون H:i',

            'duration_minutes.required' => 'مدة المهمة مطلوبة',
            'duration_minutes.integer' => 'مدة المهمة يجب أن تكون رقمًا صحيحًا بالدقائق',
            'duration_minutes.min' => 'مدة المهمة يجب ألا تقل عن 10 دقائق',
            'duration_minutes.max' => 'مدة المهمة يجب ألا تتجاوز 12 ساعة',

            'priority.required' => 'أولوية المهمة مطلوبة',
            'priority.Illuminate\Validation\Rules\Enum' => 'أولوية المهمة غير صالحة',

            'subtasks.array' => 'المهام الفرعية يجب أن تكون مصفوفة',
            'subtasks.max' => 'لا يمكن إضافة أكثر من 20 مهمة فرعية',
            'subtasks.*.title.required' => 'اسم المهمة الفرعية مطلوب',
            'subtasks.*.title.min' => 'اسم المهمة الفرعية يجب ألا يقل عن 4 محارف',
            'subtasks.*.title.max' => 'اسم المهمة الفرعية يجب ألا يتجاوز 100 حرفًا',
            'subtasks.*.title.distinct' => 'لا يمكن تكرار نفس المهمة الفرعية',

            'repeat_pattern.Illuminate\Validation\Rules\Enum' => 'نمط التكرار غير صالح',
            'repeat_weekday.required_unless' => 'يجب اختيار يوم التكرار عند تفعيل التكرار',
            'repeat_weekday.between' => 'يوم التكرار غير صالح',

            'reminder_offset_minutes.in' => 'قيمة تذكير المهمة غير صالحة',
        ];
    }
}
