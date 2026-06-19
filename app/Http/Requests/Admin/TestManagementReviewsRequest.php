<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\ApiFormRequest;

class TestManagementReviewsRequest extends ApiFormRequest
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
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:20'],
            'cursor' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'rating.integer' => 'قيمة التقييم يجب أن تكون رقماً صحيحاً.',
            'rating.min' => 'أقل تقييم مسموح هو نجمة واحدة.',
            'rating.max' => 'أعلى تقييم مسموح هو خمس نجوم.',

            'per_page.integer' => 'عدد العناصر في الصفحة يجب أن يكون رقماً صحيحاً.',
            'per_page.min' => 'عدد العناصر في الصفحة يجب أن يكون 1 على الأقل.',
            'per_page.max' => 'عدد العناصر في الصفحة لا يمكن أن يتجاوز 20.',
        ];
    }
}
