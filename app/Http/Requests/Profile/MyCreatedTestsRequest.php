<?php

namespace App\Http\Requests\Profile;

use App\Http\Requests\ApiFormRequest;

class MyCreatedTestsRequest extends ApiFormRequest
{
    protected $stopOnFirstFailure = true;
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'tab' => $this->query('tab', 'public'),
            'per_page' => $this->query('per_page', 20),
        ]);
    }

    public function rules(): array
    {
        return [
            'tab' => ['sometimes', 'string', 'in:public,private,paid'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:20'],
            'cursor' => ['sometimes', 'nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'tab.in' => 'نوع التبويب غير صحيح',
            'per_page.integer' => 'عدد العناصر في الصفحة يجب أن يكون رقمًا',
            'per_page.max' => 'لا يمكن جلب أكثر من 20 عنصرًا في الطلب الواحد',
        ];
    }
}
