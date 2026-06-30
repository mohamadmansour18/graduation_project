<?php

namespace App\Http\Requests\Settings;

use App\Enums\ThemeMode;
use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;

class UpdateThemeModeRequest extends ApiFormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'theme_mode' => [
                'required',
                'string',
                Rule::enum(ThemeMode::class),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'theme_mode.required' => 'الثيم مطلوب',
            'theme_mode.in' => 'الثيم يجب أن يكون نهاري أو ليلي',
        ];
    }
}
