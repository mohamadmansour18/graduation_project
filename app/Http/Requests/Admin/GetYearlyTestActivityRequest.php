<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\ApiFormRequest;

class GetYearlyTestActivityRequest extends ApiFormRequest
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
            'year' => [
                'nullable',
                'integer',
                'digits:4',
                'min:2025',
                'max:' . now()->year,
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'year.integer' => 'السنة يجب أن تكون رقمًا صحيحًا',
            'year.digits' => 'السنة يجب أن تتكون من 4 أرقام',
            'year.min' => 'السنة المدخلة غير صالحة',
            'year.max' => 'لا يمكن جلب إحصائيات سنة مستقبلية',
        ];
    }

    public function validatedYear(): int
    {
        return (int) ($this->validated('year') ?? now()->year);
    }
}
