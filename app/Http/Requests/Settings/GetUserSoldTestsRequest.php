<?php

namespace App\Http\Requests\Settings;

use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;

class GetUserSoldTestsRequest extends ApiFormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tab' => [
                'nullable',
                'string',
                Rule::in(['all', 'today', 'week', 'month']),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'tab.in' => 'قيمة التبويب غير صالحة',
        ];
    }

    public function tab(): string
    {
        return $this->validated('tab', 'all');
    }
}
