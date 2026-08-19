<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\ApiFormRequest;
use Closure;

class RejectAcademicVerificationRequest extends ApiFormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rejection_reason' => [
                'bail',
                'required',
                'string',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $words = preg_split('/\s+/u', trim($value), -1, PREG_SPLIT_NO_EMPTY) ?: [];

                    if (count($words) > 100) {
                        $fail('سبب الرفض يجب ألا يتجاوز 100 كلمة');
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'rejection_reason.required' => 'سبب رفض طلب التوثيق مطلوب',
            'rejection_reason.string' => 'سبب رفض طلب التوثيق يجب أن يكون نصاً',
        ];
    }
}
