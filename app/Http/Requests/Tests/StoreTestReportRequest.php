<?php

namespace App\Http\Requests\Tests;

use App\Enums\TestReportsReason;
use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;

class StoreTestReportRequest extends ApiFormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'reason' => [
                'required', Rule::enum(TestReportsReason::class),
            ],

            'description' => [
                'nullable',
                'string',
                'max:250',
                'min:10',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'سبب البلاغ مطلوب',
            'reason.enum' => 'سبب البلاغ غير صحيح',

            'description.string' => 'وصف البلاغ يجب أن يكون نصاً',
            'description.max' => 'وصف البلاغ طويل جداً',
            'description.min' => 'وصف البلاغ قصير جداً',
        ];
    }
}
