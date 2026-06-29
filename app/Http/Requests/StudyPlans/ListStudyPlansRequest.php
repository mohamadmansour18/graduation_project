<?php

namespace App\Http\Requests\StudyPlans;

use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;

class ListStudyPlansRequest extends ApiFormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'tab' => ['sometimes', 'string', Rule::in(['current', 'expired', 'future'])],
        ];
    }

    public function messages(): array
    {
        return [
            'tab.string' => 'نوع التبويب غير صالح',
            'tab.in' => 'نوع التبويب يجب أن يكون current أو expired أو future',
        ];
    }

    public function selectedTab(): string
    {
        return $this->validated('tab') ?? 'current';
    }
}
