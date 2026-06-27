<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;

class ListDashboardSalesRequest extends ApiFormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'period' => [
                'nullable',
                Rule::in(['today', 'week', 'month', 'year', 'custom']),
            ],

            'start_date' => [
                'required_if:period,custom',
                'nullable',
                'date',
                'after_or_equal:2025-01-01',
            ],

            'end_date' => [
                'required_if:period,custom',
                'nullable',
                'date',
                'after_or_equal:start_date',
            ],

            'sort_by' => [
                'nullable',
                Rule::in([
                    'purchased_at',
                    'sale_id',
                    'gross_amount',
                    'test_id',
                    'test_status',
                ]),
            ],

            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'cursor' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'period.in' => 'الفترة المحددة غير صالحة',

            'start_date.required_if' => 'تاريخ البداية مطلوب عند اختيار تاريخ مخصص',
            'start_date.date' => 'تاريخ البداية غير صالح',
            'start_date.after_or_equal' => 'لا يمكن اختيار تاريخ قبل 2025-01-01',

            'end_date.required_if' => 'تاريخ النهاية مطلوب عند اختيار تاريخ مخصص',
            'end_date.date' => 'تاريخ النهاية غير صالح',
            'end_date.after_or_equal' => 'تاريخ النهاية يجب أن يكون بعد أو يساوي تاريخ البداية',

            'sort_by.in' => 'طريقة الترتيب غير صالحة',

            'per_page.integer' => 'عدد النتائج يجب أن يكون رقماً',
            'per_page.min' => 'عدد النتائج يجب ألا يقل عن عنصر واحد',
            'per_page.max' => 'عدد النتائج يجب ألا يزيد عن 50',
        ];
    }
}
