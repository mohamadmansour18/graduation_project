<?php

namespace App\Http\Requests\Settings;

use App\Enums\TimeFormat;
use App\Enums\WeekStartsOn;
use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;

class UpdateDateTimeSettingsRequest extends ApiFormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'week_starts_on' => [
                'required',
                'string',
                Rule::enum(WeekStartsOn::class),
            ],

            'time_format' => [
                'required',
                'string',
                Rule::enum(TimeFormat::class),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'week_starts_on.required' => 'يوم بداية الأسبوع مطلوب',
            'week_starts_on.Illuminate\Validation\Rules\Enum' => 'يوم بداية الأسبوع غير صالح',

            'time_format.required' => 'نمط الساعة مطلوب',
            'time_format.Illuminate\Validation\Rules\Enum' => 'نمط الساعة غير صالح',
        ];
    }
}
