<?php

namespace App\Http\Requests\StudyPlans;

use App\Http\Requests\ApiFormRequest;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\Validator;

class GetDailyTasksOverviewRequest extends ApiFormRequest
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
            'date' => ['required', 'date_format:Y-m-d'],
            'range_start' => ['required', 'date_format:Y-m-d'],
            'range_end' => ['required', 'date_format:Y-m-d', 'after_or_equal:range_start'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $selectedDate = Carbon::createFromFormat('Y-m-d', $this->input('date'))->startOfDay();
            $rangeStart = Carbon::createFromFormat('Y-m-d', $this->input('range_start'))->startOfDay();
            $rangeEnd = Carbon::createFromFormat('Y-m-d', $this->input('range_end'))->startOfDay();

            if ($selectedDate->lt($rangeStart) || $selectedDate->gt($rangeEnd)) {
                $validator->errors()->add('date', 'التاريخ المختار يجب أن يكون ضمن الفترة المطلوبة');
            }

            if ($rangeStart->diffInDays($rangeEnd) > 16) {
                $validator->errors()->add('range_end', 'لا يمكن طلب فترة أكبر من 31 يوم');
            }
        });
    }

    public function messages(): array
    {
        return [
            'date.required' => 'تاريخ اليوم المختار مطلوب',
            'date.date_format' => 'صيغة تاريخ اليوم المختار يجب أن تكون Y-m-d',

            'range_start.required' => 'تاريخ بداية الفترة مطلوب',
            'range_start.date_format' => 'صيغة تاريخ بداية الفترة يجب أن تكون Y-m-d',

            'range_end.required' => 'تاريخ نهاية الفترة مطلوب',
            'range_end.date_format' => 'صيغة تاريخ نهاية الفترة يجب أن تكون Y-m-d',
            'range_end.after_or_equal' => 'تاريخ نهاية الفترة يجب أن يكون بعد أو يساوي تاريخ البداية',
        ];
    }
}
